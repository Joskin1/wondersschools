<?php

declare(strict_types=1);

namespace App\Filament\Resources\ResultPinBatchResource\Pages;

use App\Filament\Resources\ResultPinBatchResource;
use App\Models\ResultPin;
use App\Models\ResultPinBatch;
use App\Models\Session;
use App\Models\Student;
use App\Models\Term;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;

class ManageResultPinBatches extends ManageRecords
{
    protected static string $resource = ResultPinBatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generate_pins')
                ->label('Generate New PINs')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->modalHeading('Generate Result Checker PINs')
                ->modalDescription('Generate a batch of unique 6-digit numeric PINs for this school.')
                ->modalSubmitActionLabel('Generate PINs')
                ->form([
                    Select::make('session_id')
                        ->label('Academic Session')
                        ->options(fn () => Session::orderByDesc('id')->pluck('name', 'id')->toArray())
                        ->default(fn () => Session::where('is_active', true)->first()?->id)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn ($set) => $set('term_id', null))
                        ->helperText(function () {
                            $count = Student::count();

                            return "Total enrolled students in this school: {$count}";
                        }),

                    Select::make('term_id')
                        ->label('Term')
                        ->options(function ($get) {
                            $sessionId = $get('session_id');
                            if (! $sessionId) {
                                return [];
                            }

                            return Term::where('session_id', $sessionId)
                                ->orderBy('order')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->default(fn () => Term::where('is_active', true)->first()?->id)
                        ->required(),

                    TextInput::make('quantity')
                        ->label('Number of PINs to Generate')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(5000)
                        ->default(40)
                        ->required()
                        ->helperText('40 PINs fit on exactly one A4 page.'),
                ])
                ->action(function (array $data): void {
                    $session = Session::findOrFail($data['session_id']);
                    $term = Term::findOrFail($data['term_id']);
                    $quantity = (int) $data['quantity'];

                    $randomPart = strtoupper(Str::random(4));
                    $cleanSession = Str::slug($session->name);
                    $cleanTerm = Str::slug($term->name);
                    $batchCode = "BATCH-{$cleanSession}-{$cleanTerm}-{$randomPart}";

                    $batch = ResultPinBatch::create([
                        'batch_code' => $batchCode,
                        'session_id' => $session->id,
                        'term_id'    => $term->id,
                        'quantity'   => $quantity,
                    ]);

                    $startSerial = ResultPin::max('id') ?? 0;
                    $existingPins = ResultPin::where('session_id', $session->id)
                        ->where('term_id', $term->id)
                        ->pluck('pin')
                        ->flip()
                        ->toArray();

                    $pinsToInsert = [];
                    $now = now();

                    for ($i = 1; $i <= $quantity; $i++) {
                        do {
                            $pin = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                        } while (isset($existingPins[$pin]));

                        $existingPins[$pin] = true;
                        $serialNumber = sprintf('SN-%06d', $startSerial + $i);

                        $pinsToInsert[] = [
                            'batch_id'      => $batch->id,
                            'serial_number' => $serialNumber,
                            'pin'           => $pin,
                            'session_id'    => $session->id,
                            'term_id'       => $term->id,
                            'student_id'    => null,
                            'is_used'       => false,
                            'used_at'       => null,
                            'created_at'    => $now,
                            'updated_at'    => $now,
                        ];
                    }

                    foreach (array_chunk($pinsToInsert, 500) as $chunk) {
                        ResultPin::insert($chunk);
                    }

                    Notification::make()
                        ->title('PINs Generated Successfully')
                        ->body("Generated {$quantity} PINs for {$session->name} ({$term->name}). Batch: {$batchCode}")
                        ->success()
                        ->send();
                }),
        ];
    }
}
