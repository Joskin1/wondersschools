<x-filament-panels::page.simple>
    <form wire:submit="authenticate" class="grid gap-y-8">
        {{ $this->form }}

        <x-filament::button type="submit" class="w-full">
            Sign in
        </x-filament::button>
    </form>
</x-filament-panels::page.simple>
