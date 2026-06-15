<a href="{{ \App\Filament\Player\Pages\MissionsPage::getUrl() }}"
   class="flex items-center justify-center w-9 h-9 rounded-full text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 focus:outline-none transition-colors"
   title="Thử thách tuần">
    <div class="relative p-1">
        <x-filament::icon icon="heroicon-o-flag" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
        <span class="absolute top-0 right-0 flex h-2 w-2">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-2 w-2 bg-warning-500 ring-1 ring-white dark:ring-gray-900"></span>
        </span>
    </div>
</a>
