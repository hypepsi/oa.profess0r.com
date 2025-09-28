<x-filament::page>
    <div class="space-y-6">
        <x-filament::section heading="Add an update">
            {{ $this->form }}
        </x-filament::section>

        <x-filament::section heading="History">
            {{ $this->table }}
        </x-filament::section>
    </div>
</x-filament::page>
