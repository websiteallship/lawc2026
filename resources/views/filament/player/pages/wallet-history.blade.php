<x-filament-panels::page>
    <div class="space-y-6">

        {{-- ═══════════════════════════════════════════
             ROW 1: Số dư hiện tại
        ═══════════════════════════════════════════ --}}
        @if($wallet)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-filament::card>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-900/30">
                            <x-filament::icon icon="heroicon-o-banknotes" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Lá khả dụng</p>
                            <p class="text-xl font-bold text-emerald-600 dark:text-emerald-400 flex items-baseline gap-1">
                                <span>{{ number_format($wallet->available_balance) }}</span>
                                <span class="text-xs font-semibold text-emerald-500/75 dark:text-emerald-400/75 uppercase">lá</span>
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-amber-50 dark:bg-amber-900/30">
                            <x-filament::icon icon="heroicon-o-lock-closed" class="w-5 h-5 text-amber-500 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Đang khóa</p>
                            <p class="text-xl font-bold text-amber-500 dark:text-amber-400 flex items-baseline gap-1">
                                <span>{{ number_format($wallet->locked_balance) }}</span>
                                <span class="text-xs font-semibold text-amber-500/75 dark:text-amber-400/75 uppercase">lá</span>
                            </p>
                        </div>
                    </div>
                </x-filament::card>

                <x-filament::card>
                    <div class="flex items-center gap-3">
                        <div class="p-2.5 rounded-xl bg-gray-100 dark:bg-gray-800">
                            <x-filament::icon icon="heroicon-o-wallet" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">Tổng lá</p>
                            <p class="text-xl font-bold text-gray-700 dark:text-gray-200 flex items-baseline gap-1">
                                <span>{{ number_format($wallet->total_balance) }}</span>
                                <span class="text-xs font-semibold text-gray-500/75 dark:text-gray-400/75 uppercase">lá</span>
                            </p>
                        </div>
                    </div>
                </x-filament::card>
            </div>

            {{-- ═══════════════════════════════════════════
                 ROW 2: Dòng lá lưu hành + filter period
            ═══════════════════════════════════════════ --}}
            <x-filament::card>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-arrow-path" class="w-4 h-4 text-emerald-500" />
                        Dòng lá lưu hành
                        @php
                            $periodOptions = \App\Support\DatePeriodFilter::options();
                            $periodLabel = $periodOptions[$flowPeriod] ?? 'Toàn mùa';
                        @endphp
                        <span class="text-xs font-normal text-gray-400 normal-case tracking-normal">
                            ({{ $periodLabel }})
                        </span>
                    </h3>
                    <div>
                        <select wire:model.live="flowPeriod"
                                class="text-xs py-1.5 px-3 rounded-xl border border-gray-200 bg-white text-gray-700 shadow-sm focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-200 transition-all duration-200">
                            @foreach($periodOptions as $val => $lbl)
                                <option value="{{ $val }}" {{ $flowPeriod === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @php $flow = $this->getFlowStats() @endphp
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    {{-- Nhận vào --}}
                    <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-900/10 p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <x-filament::icon icon="heroicon-m-arrow-down-circle" class="w-4 h-4 text-emerald-600" />
                            <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Tổng nhận vào</span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400">+{{ number_format($flow['received']) }}</span>
                            <span class="text-xs font-semibold text-emerald-600/75 dark:text-emerald-400/75 uppercase">lá</span>
                        </div>
                    </div>

                    {{-- Chi ra --}}
                    <div class="rounded-xl border border-red-100 dark:border-red-900/50 bg-red-50/50 dark:bg-red-900/10 p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <x-filament::icon icon="heroicon-m-arrow-up-circle" class="w-4 h-4 text-red-500" />
                            <span class="text-xs font-medium text-red-600 dark:text-red-400">Tổng chi ra</span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-black text-red-500 dark:text-red-400">-{{ number_format($flow['spent']) }}</span>
                            <span class="text-xs font-semibold text-red-500/75 dark:text-red-400/75 uppercase">lá</span>
                        </div>
                    </div>

                    {{-- Lãi ròng --}}
                    <div class="rounded-xl border {{ $flow['net'] >= 0 ? 'border-blue-100 dark:border-blue-900/50 bg-blue-50/50 dark:bg-blue-900/10' : 'border-orange-100 dark:border-orange-900/50 bg-orange-50/50 dark:bg-orange-900/10' }} p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <x-filament::icon icon="heroicon-m-scale" class="w-4 h-4 {{ $flow['net'] >= 0 ? 'text-blue-500' : 'text-orange-500' }}" />
                            <span class="text-xs font-medium {{ $flow['net'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}">Lãi ròng kỳ</span>
                        </div>
                        <div class="flex items-baseline gap-1">
                            <span class="text-2xl font-black {{ $flow['net'] >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-orange-500 dark:text-orange-400' }}">
                                {{ $flow['net'] >= 0 ? '+' : '' }}{{ number_format($flow['net']) }}
                            </span>
                            <span class="text-xs font-semibold {{ $flow['net'] >= 0 ? 'text-blue-600/75 dark:text-blue-400/75' : 'text-orange-500/75 dark:text-orange-400/75' }} uppercase">lá</span>
                        </div>
                    </div>
                </div>
            </x-filament::card>

        @else
            <x-filament::card>
                <p class="text-center text-gray-400 py-4">Chưa có ví lá cho mùa giải này.</p>
            </x-filament::card>
        @endif

        {{-- ═══════════════════════════════════════════
             BIỂU ĐỒ DÒNG LÁ (Widget renders above via getHeaderWidgets)
        ═══════════════════════════════════════════ --}}

        {{-- ═══════════════════════════════════════════
             BẢNG ĐỐI SOÁT KẾ TOÁN
        ═══════════════════════════════════════════ --}}
        @if($wallet)
            @php $recon = $this->getReconciliation() @endphp
            <x-filament::card>
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-table-cells" class="w-4 h-4 text-blue-500" />
                        Bảng đối soát kế toán
                        @php
                            $periodOptions = \App\Support\DatePeriodFilter::options();
                            $periodLabel = $periodOptions[$flowPeriod] ?? 'Toàn mùa';
                        @endphp
                        <span class="text-xs font-normal text-gray-400 normal-case tracking-normal">
                            ({{ $periodLabel }})
                        </span>
                    </h3>
                    <button
                        wire:click="exportCsv"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-300 text-emerald-700 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-700 dark:hover:bg-emerald-900/50 transition-all duration-200 shadow-sm"
                    >
                        <x-filament::icon icon="heroicon-m-arrow-down-tray" class="w-4 h-4" />
                        Tải đối soát (.csv)
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                                <th class="text-left py-2 px-3 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Loại giao dịch</th>
                                <th class="text-right py-2 px-3 text-xs font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Nhận vào (+)</th>
                                <th class="text-right py-2 px-3 text-xs font-bold text-red-500 dark:text-red-400 uppercase tracking-wider">Chi ra (-)</th>
                                <th class="text-right py-2 px-3 text-xs font-bold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Ròng</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($recon['rows'] as $row)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
                                    <td class="py-2.5 px-3 text-gray-700 dark:text-gray-300">{{ $row['label'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono {{ $row['received'] > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400' }}">
                                        {{ $row['received'] > 0 ? '+' . number_format($row['received']) : '—' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono {{ $row['spent'] > 0 ? 'text-red-500 dark:text-red-400' : 'text-gray-400' }}">
                                        {{ $row['spent'] > 0 ? '-' . number_format($row['spent']) : '—' }}
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono font-semibold {{ $row['net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }}">
                                        {{ ($row['net'] >= 0 ? '+' : '') . number_format($row['net']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="py-4 text-center text-gray-400">Chưa có giao dịch trong kỳ này.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50">
                                <td class="py-3 px-3 text-xs font-black text-gray-700 dark:text-gray-200 uppercase tracking-wider">Tổng cộng</td>
                                <td class="py-3 px-3 text-right font-mono font-black text-emerald-600 dark:text-emerald-400">
                                    +{{ number_format($recon['total_received']) }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-black text-red-500 dark:text-red-400">
                                    -{{ number_format($recon['total_spent']) }}
                                </td>
                                <td class="py-3 px-3 text-right font-mono font-black {{ $recon['total_net'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }}">
                                    {{ ($recon['total_net'] >= 0 ? '+' : '') . number_format($recon['total_net']) }}
                                </td>
                            </tr>
                            <tr class="border-t border-gray-200 dark:border-gray-700">
                                <td class="py-2 px-3 text-xs text-gray-500">Số dư đầu kỳ</td>
                                <td colspan="2"></td>
                                <td class="py-2 px-3 text-right font-mono text-sm font-semibold text-gray-600 dark:text-gray-300">
                                    {{ number_format($recon['opening_balance']) }} lá
                                </td>
                            </tr>
                            <tr>
                                <td class="py-2 px-3 text-xs font-bold text-gray-700 dark:text-gray-200">Số dư cuối kỳ</td>
                                <td colspan="2"></td>
                                <td class="py-2 px-3 text-right font-mono text-base font-black text-emerald-600 dark:text-emerald-400">
                                    {{ number_format($recon['closing_balance']) }} lá
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </x-filament::card>
        @endif

        {{-- ═══════════════════════════════════════════
             FILTER + SEARCH + LEDGER LIST
        ═══════════════════════════════════════════ --}}
        <x-filament::card>
            <div class="space-y-4 mb-4 border-b border-gray-100 dark:border-gray-800 pb-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    {{-- Search input --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-4 h-4 text-gray-400" />
                        </div>
                        <input type="search" wire:model.live.debounce.500ms="searchQuery"
                               class="block w-full py-1.5 pl-9 pr-3 text-sm text-gray-900 border border-gray-200 rounded-xl bg-gray-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:placeholder-gray-400 dark:text-white shadow-sm transition-all duration-200"
                               placeholder="Tìm mã phiếu, trận đấu...">
                    </div>

                    {{-- Period Select --}}
                    <div>
                        <select wire:model.live="filterPeriod"
                                class="block w-full py-1.5 px-3 text-sm text-gray-900 border border-gray-200 rounded-xl bg-gray-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white shadow-sm transition-all duration-200">
                            <option value="all">Mọi thời gian</option>
                            <option value="today">Hôm nay</option>
                            <option value="yesterday">Hôm qua</option>
                            <option value="week">7 ngày qua</option>
                            <option value="month">30 ngày qua</option>
                        </select>
                    </div>

                    {{-- Type Group Select --}}
                    <div>
                        <select wire:model.live="filterTypeGroup"
                                class="block w-full py-1.5 px-3 text-sm text-gray-900 border border-gray-200 rounded-xl bg-gray-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white shadow-sm transition-all duration-200">
                            <option value="all">Tất cả loại giao dịch</option>
                            <option value="bet_placed">Đặt cược</option>
                            <option value="bet_win">Thắng cược</option>
                            <option value="bet_lose">Thua cược</option>
                            <option value="bet_refund">Hòa & Hủy cược</option>
                            <option value="admin_adj">Admin cấp/trừ</option>
                        </select>
                    </div>

                    {{-- Amount Select --}}
                    <div>
                        <select wire:model.live="filterAmount"
                                class="block w-full py-1.5 px-3 text-sm text-gray-900 border border-gray-200 rounded-xl bg-gray-50 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 dark:bg-gray-800 dark:border-gray-700 dark:text-white shadow-sm transition-all duration-200">
                            <option value="all">Mọi số tiền</option>
                            <option value="1k">Từ 1,000 lá</option>
                            <option value="10k">Từ 10,000 lá</option>
                            <option value="100k">Từ 100,000 lá</option>
                        </select>
                    </div>
                </div>

                {{-- Detailed sub-type pills --}}
                @if($filterTypeGroup === 'all')
                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                        <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider mr-1">Chi tiết:</span>
                        @foreach($this->getLedgerTypeOptions() as $val => $label)
                            <button
                                wire:click="$set('filterType', '{{ $val }}')"
                                class="text-[11px] px-2.5 py-0.5 rounded-full border transition-all duration-200
                                    {{ $filterType === $val
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm font-semibold'
                                        : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50 hover:text-gray-900 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700' }}"
                            >{{ $label }}</button>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Ledger list --}}
            @php $ledgers = $this->getLedgers() @endphp
            @forelse($ledgers as $ledger)
                @php
                    $netAvail  = $ledger->amount_available;
                    $netLocked = $ledger->amount_locked;
                    $match     = $ledger->bet?->market?->match;
                @endphp
                <div
                    @if($ledger->bet_id)
                        wire:click="mountAction('viewBet', { bet_id: {{ $ledger->bet_id }} })"
                        class="flex items-start justify-between py-3.5 px-2 -mx-2 rounded-xl border-b border-gray-100 dark:border-gray-700/60 last:border-0 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors duration-150 group"
                    @else
                        class="flex items-start justify-between py-3.5 border-b border-gray-100 dark:border-gray-700/60 last:border-0"
                    @endif
                >
                    {{-- Left: icon + info --}}
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        {{-- Flow icon --}}
                        <div class="mt-0.5 shrink-0">
                            @if($netAvail > 0)
                                <div class="p-1 rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                                    <x-filament::icon icon="heroicon-m-arrow-down-left" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                                </div>
                            @elseif($netAvail < 0)
                                <div class="p-1 rounded-full bg-red-100 dark:bg-red-900/40">
                                    <x-filament::icon icon="heroicon-m-arrow-up-right" class="w-3.5 h-3.5 text-red-500 dark:text-red-400" />
                                </div>
                            @else
                                <div class="p-1 rounded-full bg-gray-100 dark:bg-gray-800">
                                    <x-filament::icon icon="heroicon-m-minus" class="w-3.5 h-3.5 text-gray-400" />
                                </div>
                            @endif
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-1.5 mb-0.5">
                                <x-filament::badge
                                    :color="$ledger->type->getColor()"
                                    size="sm"
                                >
                                    {{ $ledger->type->label() }}
                                </x-filament::badge>

                                @if($ledger->bet?->public_code)
                                    <span class="font-mono text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">
                                        {{ $ledger->bet->public_code }}
                                    </span>
                                @endif

                                @if($ledger->bet_id)
                                    <x-filament::icon icon="heroicon-m-chevron-right" class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-emerald-500 transition-colors" />
                                @endif
                            </div>

                            @if($match)
                                <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 truncate">
                                    {{ $match->home_team }} vs {{ $match->away_team }}
                                </p>
                            @elseif($ledger->reason)
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $ledger->reason }}</p>
                            @endif

                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                {{ $ledger->created_at?->setTimezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}
                            </p>
                        </div>
                    </div>

                    {{-- Right: amounts --}}
                    <div class="text-right ml-4 shrink-0">
                        @if($netAvail !== 0)
                            <p class="text-sm font-bold {{ $netAvail > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-500 dark:text-red-400' }}">
                                {{ $netAvail > 0 ? '+' : '' }}{{ number_format($netAvail) }} lá
                            </p>
                        @endif
                        @if($netLocked !== 0)
                            <p class="text-xs text-amber-500 dark:text-amber-400">
                                khóa: {{ $netLocked > 0 ? '+' : '' }}{{ number_format($netLocked) }}
                            </p>
                        @endif
                        <p class="text-xs text-gray-400 dark:text-gray-500">
                            còn {{ number_format($ledger->balance_available_after) }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="py-12 text-center">
                    <x-filament::icon icon="heroicon-o-inbox" class="w-10 h-10 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                    <p class="text-gray-400 dark:text-gray-500">Chưa có lịch sử giao dịch.</p>
                </div>
            @endforelse

            @if($ledgers instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
                <div class="mt-4 border-t border-gray-100 dark:border-gray-800 pt-4">
                    <x-filament::pagination :paginator="$ledgers" />
                </div>
            @endif
        </x-filament::card>
    </div>
</x-filament-panels::page>
