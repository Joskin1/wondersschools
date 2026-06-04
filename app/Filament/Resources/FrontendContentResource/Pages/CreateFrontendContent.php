<?php

namespace App\Filament\Resources\FrontendContentResource\Pages;

use App\Filament\Resources\FrontendContentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFrontendContent extends CreateRecord
{
    protected static string $resource = FrontendContentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $key = $data['key'] ?? null;
        
        $isImage = str_ends_with($key, '_image') || 
                   str_ends_with($key, '_logo') || 
                   $key === 'hero_images' || 
                   $key === 'site_logo';

        $isTextarea = ! $isImage && (
                   str_contains($key, 'description') || 
                   str_contains($key, 'text') || 
                   str_contains($key, 'mission') || 
                   str_contains($key, 'vision') || 
                   str_contains($key, 'body') || 
                   str_contains($key, 'icon')
        );

        if ($isImage) {
            $data['value'] = $data['value_image'] ?? null;
        } elseif ($isTextarea) {
            $data['value'] = $data['value_textarea'] ?? null;
        } else {
            $data['value'] = $data['value_text'] ?? null;
        }

        unset($data['value_image'], $data['value_textarea'], $data['value_text']);

        return $data;
    }
}
