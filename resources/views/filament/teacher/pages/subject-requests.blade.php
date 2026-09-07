<x-filament-panels::page>
    <div class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">
                Select Classes & Subject to Teach
            </x-slot>
            <x-slot name="description">
                Choose the subject you wish to teach and select all applicable classes. Once submitted, school administrators will receive a notification to review and approve your request.
            </x-slot>

            <form wire:submit="submitRequest" class="space-y-6">
                {{ $this->form }}

                <div class="flex justify-end">
                    <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                        Submit for Approval
                    </x-filament::button>
                </div>
            </form>
        </x-filament::section>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
