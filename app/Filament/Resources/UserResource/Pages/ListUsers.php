<?php

namespace App\Filament\Resources\UserResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Actions\Action;
use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('copy_teacher_registration_link')
                ->label('Copy Teacher Registration Link')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('success')
                ->modalHeading('Teacher Self-Registration Link')
                ->modalDescription('Share this link with teachers in your staff group. Anyone using this link can register their teacher profile. Portal access will remain pending until you activate it.')
                ->modalContent(function () {
                    $link = route('public.teacher.register');
                    return view('filament.components.copy-teacher-link-modal', ['link' => $link]);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close'),
            CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder | Relation | null
    {
        $query = parent::getTableQuery();

        if (! $query instanceof Builder) {
            return $query;
        }

        $search = trim((string) $this->getTableSearch());

        if ($search === '') {
            return $query->whereNull('deleted_at');
        }

        return $query->where(function (Builder $query) use ($search): void {
            $query
                ->whereNull('deleted_at')
                ->orWhere(function (Builder $query) use ($search): void {
                    $query
                        ->whereNotNull('deleted_at')
                        ->where(function (Builder $query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
        });
    }

    public function getTabs(): array
    {
        $isSudo = auth()->user()?->isSudo();

        $tabs = [
            'all' => Tab::make('All')
                ->badge(fn () => UserResource::getEloquentQuery()->whereNull('deleted_at')->count()),
            'students' => Tab::make('Students')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('role', 'student'))
                ->badge(fn () => UserResource::getEloquentQuery()->whereNull('deleted_at')->where('role', 'student')->count())
                ->badgeColor('info'),
            'teachers' => Tab::make('Teachers')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('role', 'teacher'))
                ->badge(fn () => UserResource::getEloquentQuery()->whereNull('deleted_at')->where('role', 'teacher')->count())
                ->badgeColor('success'),
            'admins' => Tab::make('Admins')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('role', 'admin'))
                ->badge(fn () => UserResource::getEloquentQuery()->whereNull('deleted_at')->where('role', 'admin')->count())
                ->badgeColor('warning'),
        ];

        if ($isSudo) {
            $tabs['sudo'] = Tab::make('Sudo')
                ->modifyQueryUsing(fn (\Illuminate\Database\Eloquent\Builder $query) => $query->where('role', 'sudo'))
                ->badge(fn () => \App\Models\User::where('role', 'sudo')->count())
                ->badgeColor('danger');
        }

        return $tabs;
    }
}
