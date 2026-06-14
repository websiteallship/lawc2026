<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex justify-end gap-3">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    Lưu cài đặt
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament-panels::page>
