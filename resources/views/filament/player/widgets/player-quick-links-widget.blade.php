<x-filament-widgets::widget>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-filament::button
            tag="a"
            :href="App\Filament\Player\Pages\MatchListPage::getUrl()"
            icon="heroicon-o-calendar"
            size="xl"
            color="primary"
        >
            Xem trận đấu
        </x-filament::button>
        <x-filament::button
            tag="a"
            :href="App\Filament\Player\Pages\MyBetsPage::getUrl()"
            icon="heroicon-o-ticket"
            size="xl"
            color="gray"
        >
            Phiếu của tôi
        </x-filament::button>
    </div>
</x-filament-widgets::widget>
