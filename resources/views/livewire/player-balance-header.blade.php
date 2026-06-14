<div class="flex items-center gap-2 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-full border border-emerald-100 dark:border-emerald-800" wire:poll.10s>
    <x-filament::icon icon="heroicon-o-wallet" class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
    <div class="flex flex-col">
        
        <span class="text-sm font-black text-emerald-700 dark:text-emerald-300 leading-none">{{ number_format($balance) }}</span>
    </div>
</div>
