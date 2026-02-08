<div>
    <x-filament-panels::page>
        <form wire:submit="generate">
            {{ $this->form }}
            
            <div class="mt-6">
                @foreach ($this->getFormActions() as $action)
                    {{ $action }}
                @endforeach
            </div>
        </form>
    </x-filament-panels::page>
</div>
