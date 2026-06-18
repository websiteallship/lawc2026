<div>
    @if($currentModal)
        @include("livewire.player.modals.{$currentModal}", $modalData[$currentModal] ?? [])
    @endif

    @if(config('app.debug'))
        <div class="fixed bottom-4 right-4 z-50">
            @if($isDevModeOpen)
                <div class="bg-gray-800 border border-gray-700 rounded-lg shadow-xl p-4 mb-2 w-64">
                    <h3 class="text-xs font-bold text-gray-400 uppercase mb-3">Dev Mode: Test Modals</h3>
                    <div class="flex flex-col space-y-2">
                        <button wire:click="forceShowModal('daily')" class="text-left text-sm text-gray-200 hover:text-white hover:bg-gray-700 px-2 py-1 rounded">M1 - Daily Briefing</button>
                        <button wire:click="forceShowModal('ranking')" class="text-left text-sm text-gray-200 hover:text-white hover:bg-gray-700 px-2 py-1 rounded">M6 - Daily Ranking</button>
                        <button wire:click="forceShowModal('celebration')" class="text-left text-sm text-gray-200 hover:text-white hover:bg-gray-700 px-2 py-1 rounded">M2 - Celebration</button>
                        <button wire:click="forceShowModal('reminder')" class="text-left text-sm text-gray-200 hover:text-white hover:bg-gray-700 px-2 py-1 rounded">M3 - Match Reminder</button>
                        <button wire:click="forceShowModal('settlement')" class="text-left text-sm text-gray-200 hover:text-white hover:bg-gray-700 px-2 py-1 rounded">M4 - Settlement</button>
                        <button wire:click="forceShowModal('reengagement')" class="text-left text-sm text-gray-200 hover:text-white hover:bg-gray-700 px-2 py-1 rounded">M5 - Reengagement</button>
                        <hr class="border-gray-600 my-1">
                        <button wire:click="forceShowModal('all')" class="text-left text-sm text-primary-400 hover:text-primary-300 hover:bg-gray-700 px-2 py-1 rounded font-medium">Test Queue All (M1 -> M6)</button>
                    </div>
                </div>
            @endif
            
            <button wire:click="toggleDevMode" class="bg-primary-600 hover:bg-primary-500 text-white rounded-full p-3 shadow-lg flex items-center justify-center transition ml-auto block">
                <x-heroicon-o-bug-ant class="w-5 h-5" />
            </button>
        </div>
    @endif
</div>
