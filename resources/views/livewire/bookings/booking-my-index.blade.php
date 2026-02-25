<div x-data="{}" x-init="
    @if($highlightId)
        setTimeout(() => {
            const element = document.getElementById('booking-row-{{ $highlightId }}') || document.getElementById('booking-card-{{ $highlightId }}');
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });

                // Add blinking animation
                let blinkCount = 0;
                const blinkInterval = setInterval(() => {
                    if (blinkCount % 2 === 0) {
                        element.classList.remove('bg-gray-100', 'dark:bg-zinc-700');
                    } else {
                        element.classList.add('bg-gray-100', 'dark:bg-zinc-700');
                    }
                    blinkCount++;

                    // Stop after 2 blinks (1 complete cycle at 700ms interval)
                    if (blinkCount >= 2) {
                        clearInterval(blinkInterval);
                        element.classList.remove('bg-gray-100', 'dark:bg-zinc-700');
                    }
                }, 700);
            }
        }, 100);
    @endif
">
<div class="relative mb-6 w-full">
        <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start gap-4">
            <!-- Header Section -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('My Bookings') }}</h1>
                <p class="text-gray-600 mt-1 dark:text-gray-400">{{ __('Manage all bookings here') }}</p>
            </div>
            
            <!-- Quick Stats -->
            <div class="flex gap-4 text-sm lg:gap-6">
                <div class="text-center">
                    <div class="font-semibold text-yellow-600">{{ $bookings->where('status', 'pending')->count() }}</div>
                    <div class="text-gray-500">Pending</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-sky-600">{{ $bookings->where('status', 'approved')->count() }}</div>
                    <div class="text-gray-500">Approved</div>
                </div>
                <div class="text-center">
                    <div class="font-semibold text-green-600">{{ $bookings->where('status', 'done')->count() }}</div>
                    <div class="text-gray-500">Completed</div>
                </div>
            </div>
        </div>
        <flux:separator variant="subtle" class="mt-4" />        
    </div>

    @if (session()->has('success'))
        <div class="mb-4">
            <div x-data="{ visible: true }" x-show="visible" x-collapse>
                <div x-show="visible" x-transition>
                    <flux:callout icon="check-circle" variant="success" heading="{{ session('success') }}">                  
                        <x-slot name="controls">
                            <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
                        </x-slot>
                    </flux:callout>
                </div>
            </div>  
        </div>
    @endif   
     
    @if (session()->has('error'))
        <div x-data="{ visible: true }" x-show="visible" x-collapse>
            <div x-show="visible" x-transition>
                <flux:callout icon="x-circle" variant="danger" heading="{{ session('error') }}">                  
                    <x-slot name="controls">
                        <flux:button icon="x-mark" variant="ghost" x-on:click="visible = false" />
                    </x-slot>
                </flux:callout>
            </div>
        </div>   
    @endif    

    @session('error')
        <div class="mb-4">
            <flux:callout variant="danger" icon="exclamation-triangle" heading="{{ $value }}" />
        </div>
    @endsession

    <!-- Action Bar -->
    <div
        class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4"
        x-data="{
            showOverdueModal: false,
            overdueBookings: @js($overdueBookings->map(fn($b) => [
                'id'         => $b->id,
                'asset_label'=> $b->asset_type_label,
                'asset_name' => match($b->asset_type_label) {
                    'Vehicle'      => ($b->asset?->model ?? 'N/A') . ($b->asset?->plate_number ? ' ('.$b->asset->plate_number.')' : ''),
                    default        => $b->asset?->name ?? 'N/A',
                },
                'end_time'   => \Carbon\Carbon::parse($b->end_time)->format('M d, Y h:i A'),
                'show_url'   => route('bookings.show.user', ['id' => $b->id]),
            ])),
            createUrl: '{{ route('bookings.create') }}',
            tryCreate() {
                if (this.overdueBookings.length > 0) {
                    this.showOverdueModal = true;
                } else {
                    window.location.href = this.createUrl;
                }
            }
        }"
    >
        <div class="">
            <!-- Create Button -->
            @can('book.create')
                <div class="flex justify-between items-center">
                    <flux:button variant="primary" icon="plus" class="w-full sm:w-auto" @click="tryCreate()">
                        Create New Booking
                    </flux:button>
                </div>
            @endcan
        </div>

        {{-- ── Overdue Bookings Warning Modal ── --}}
        <div
            x-show="showOverdueModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="display:none;"
        >
            <div class="absolute inset-0 bg-black/50"></div>

            <div
                x-show="showOverdueModal"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative z-10 w-full max-w-lg bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl overflow-hidden"
                style="display:none;"
                @click.stop
            >
                {{-- Header --}}
                <div class="flex items-start gap-3 p-6 pb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Action Required Before Booking</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                            You have <span class="font-semibold text-amber-600 dark:text-amber-400" x-text="overdueBookings.length"></span>
                            overdue booking<span x-show="overdueBookings.length > 1">s</span> that must be marked as <strong>Done</strong> before creating a new booking.
                        </p>
                    </div>
                    <button @click="showOverdueModal = false" type="button" class="flex-shrink-0 w-7 h-7 flex items-center justify-center rounded-full bg-gray-100 hover:bg-gray-200 dark:bg-zinc-800 dark:hover:bg-zinc-700 text-gray-500 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="border-t border-gray-100 dark:border-zinc-800 mx-6"></div>

                {{-- Overdue list --}}
                <div class="p-6 pt-4 space-y-3 max-h-72 overflow-y-auto">
                    <template x-for="booking in overdueBookings" :key="booking.id">
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                            <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="booking.asset_name"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
                                    <span x-text="booking.asset_label"></span>
                                    <span class="opacity-40">·</span>
                                    <span class="text-red-500 dark:text-red-400">Ended <span x-text="booking.end_time"></span></span>
                                </div>
                            </div>
                            <a
                                :href="booking.show_url"
                                class="flex-shrink-0 inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium transition-colors"
                            >
                                Mark Done
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="border-t border-gray-100 dark:border-zinc-800 px-6 py-4 flex justify-end">
                    <flux:button variant="ghost" size="sm" @click="showOverdueModal = false">Close</flux:button>
                </div>
            </div>
        </div>
        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 w-full md:w-auto">
            <flux:select wire:model.live="statusFilter" placeholder="All Status" class="min-w-32 w-full sm:w-auto">
                <flux:select.option value="">All Status</flux:select.option>
                <flux:select.option value="pending">Pending</flux:select.option>
                <flux:select.option value="approved">Approved</flux:select.option>
                <flux:select.option value="rejected">Rejected</flux:select.option>
                <flux:select.option value="cancelled">Cancelled</flux:select.option>
                <flux:select.option value="done">Done</flux:select.option>
            </flux:select>
            
            <flux:select wire:model.live="assetTypeFilter" placeholder="All Assets" class="min-w-32 w-full sm:w-auto">
                <flux:select.option value="">All Assets</flux:select.option>
                <flux:select.option value="App\Models\Vehicle">Vehicle</flux:select.option>
                <flux:select.option value="App\Models\MeetingRoom">Meeting Room</flux:select.option>
                <flux:select.option value="App\Models\ItAsset">IT Asset</flux:select.option>
            </flux:select>

            <flux:input 
                wire:model.live.debounce.500ms="search" 
                placeholder="Search bookings..." 
                icon="magnifying-glass"
                class="min-w-48 w-full sm:w-auto"
            />

            <!-- Reset Button - Icon on desktop, Text on mobile -->
            <flux:tooltip content="Reset Filters" class="hidden sm:block">
                <flux:button 
                    wire:click="resetFilters" 
                    icon="arrow-path"
                    class="hidden"
                />
            </flux:tooltip>
            
            <!-- Reset Button for Mobile - Full width with text -->
            <flux:button 
                wire:click="resetFilters" 
                icon="arrow-path"
                class="sm:hidden w-full" 
                variant="filled"
            >
                Reset Filters
            </flux:button>           
        </div>
    </div>

    <div class="hidden md:block sm:block bg-white dark:bg-zinc-900 rounded-xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-700">
                <thead class="bg-gray-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">#</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Asset Details</th>
                        <th wire:click="sortBy('start_time')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700 select-none">
                            <div class="flex items-center space-x-1">
                                <span>Duration</span>
                                @if($sortField === 'start_time')
                                    @if($sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('created_at')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700 select-none">
                            <div class="flex items-center space-x-1">
                                <span>Booked</span>
                                @if($sortField === 'created_at')
                                    @if($sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('status')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-zinc-700 select-none">
                            <div class="flex items-center space-x-1">
                                <span>Status</span>
                                @if($sortField === 'status')
                                    @if($sortDirection === 'asc')
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    @endif
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-zinc-700">
                    @forelse ($bookings as $booking)
                        <tr id="booking-row-{{ $booking->id }}"
                            class="hover:bg-gray-50 dark:hover:bg-zinc-800 transition-colors duration-300 {{ $highlightId == $booking->id ? 'bg-gray-100 dark:bg-zinc-700' : '' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ ($bookings->currentPage() - 1) * $bookings->perPage() + $loop->iteration }}
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <flux:badge size="sm" icon="{{ $booking->asset_type_label == 'Vehicle' ? 'car' : ($booking->asset_type_label == 'Meeting Room' ? 'building-office' : 'computer-desktop') }}" color="{{ $booking->asset_type_label == 'Vehicle' ? 'green' : ($booking->asset_type_label == 'Meeting Room' ? 'blue' : 'fuchsia') }}">
                                    {{ $booking->asset_type_label }}
                                </flux:badge>
                                <div class="text-sm font-medium text-gray-900 dark:text-white mt-1">
                                    @if ($booking->asset_type_label == 'Vehicle')
                                        {{ $booking->asset?->model ?? 'N/A' }}
                                        @if($booking->asset?->plate_number)
                                            <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $booking->asset->plate_number }}</span>
                                        @endif
                                    @else
                                        {{ $booking->asset?->name ?? 'N/A' }}
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    @php
                                        $start = \Carbon\Carbon::parse($booking->start_time);
                                        $end = \Carbon\Carbon::parse($booking->end_time);
                                        $totalMinutes = $start->diffInMinutes($end);
                                        $totalHours = floor($totalMinutes / 60);
                                        $days = (int) $start->diffInDays($end);
                                        
                                        // Enhanced date display logic
                                        if ($days > 1) {
                                            // Multi-day booking
                                            if ($start->month === $end->month) {
                                                // Same month: "Aug 14 - 17, 2025"
                                                $dateDisplay = $start->format('M j') . ' - ' . $end->format('j, Y');
                                            } else {
                                                // Different months: "Aug 29 - Sept 2, 2025"
                                                $dateDisplay = $start->format('M j') . ' - ' . $end->format('M j, Y');
                                            }
                                        } else {
                                            // Same day booking
                                            $dateDisplay = $start->format('M d, Y');
                                        }
                                        
                                        // Duration calculation
                                        if ($totalMinutes < 60) {
                                            $durationText = $totalMinutes . 'm';
                                        } elseif ($totalHours < 24) {
                                            $hours = $totalHours;
                                            $minutes = $totalMinutes % 60;
                                            $durationText = $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
                                        } else {
                                            $hours = $totalHours % 24;
                                            $durationText = $days . 'd' . ($hours > 0 ? ' ' . $hours . 'h' : '');
                                        }
                                    @endphp

                                    <div class="font-medium">{{ $dateDisplay }}</div>
                                    @if($days === 0)
                                        {{-- Single day booking - show time range --}}
                                        <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                            <flux:icon name="clock" class="w-3 h-3" />
                                            <flux:text class="text-xs">
                                                {{ $start->format('h:i A') }} - {{ $end->format('h:i A') }}
                                            </flux:text>
                                        </div>
                                    @else
                                        {{-- Multi-day booking - show start and end times --}}
                                        <div class="text-xs text-gray-500 flex items-center gap-1 mt-1">
                                            <flux:icon name="clock" class="w-3 h-3" />
                                            <flux:text class="text-xs">
                                                {{ $start->format('h:i A') }} → {{ $end->format('h:i A') }}
                                            </flux:text>
                                        </div>
                                    @endif
                                    <div class="text-xs text-gray-400 mt-1">
                                        {{ $durationText }}
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900 dark:text-white">
                                    {{ \Carbon\Carbon::parse($booking->created_at)->diffForHumans() }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ \Carbon\Carbon::parse($booking->created_at)->format('M d, h:i A') }}
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    @if ($booking->status == 'pending')
                                        <flux:badge color="yellow" class="w-fit">
                                            <flux:icon name="clock" class="w-3 h-3 mr-1" />
                                            {{ ucwords($booking->status) }}
                                        </flux:badge>
                                        <div class="text-xs text-gray-500 mt-1">Awaiting approval</div>
                                    @elseif ($booking->status == 'approved')     
                                        <flux:badge color="sky" class="w-fit">
                                            <flux:icon name="check" class="w-3 h-3 mr-1" />
                                            {{ ucwords($booking->status) }}
                                        </flux:badge>
                                        @if(\Carbon\Carbon::parse($booking->start_time)->isToday())
                                            <flux:badge color="orange" size="sm" class="w-fit text-xs">Starts Today</flux:badge>
                                        @elseif(\Carbon\Carbon::parse($booking->start_time)->isTomorrow())
                                            <flux:badge color="blue" size="sm" class="w-fit text-xs">Starts Tomorrow</flux:badge>
                                        @endif
                                    @elseif ($booking->status == 'rejected')     
                                        <flux:badge color="red" class="w-fit">
                                            <flux:icon name="x-mark" class="w-3 h-3 mr-1" />
                                            {{ ucwords($booking->status) }}
                                        </flux:badge>
                                        <div class="text-xs text-red-600 mt-1">Request denied</div>                                                                                               
                                    @elseif ($booking->status == 'cancelled')
                                        <flux:badge color="zinc" class="w-fit">
                                            <flux:icon name="arrow-turn-down-left" class="w-3 h-3 mr-1" />
                                            {{ ucwords($booking->status) }}
                                        </flux:badge>
                                        <div class="text-xs text-gray-500 mt-1">You cancelled this</div>
                                    @elseif ($booking->status == 'done')
                                        <flux:badge color="green" class="w-fit">
                                            <flux:icon name="check-circle" class="w-3 h-3 mr-1" />
                                            {{ ucwords($booking->status) }}
                                        </flux:badge>
                                        <div class="text-xs text-green-600 mt-1">Booking completed</div>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <flux:button size="xs" href="{{ route('bookings.show.user', ['id' => $booking->id, 'page' => $bookings->currentPage()]) }}" variant="ghost">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </flux:button>

                                    @if($booking->status == 'pending' && \Carbon\Carbon::parse($booking->start_time) > now())
                                        @can('book.edit')
                                        <flux:button size="xs" href="{{ route('bookings.edit.user', ['booking' => $booking->id, 'page' => $bookings->currentPage()]) }}" variant="ghost">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </flux:button>
                                        @endcan
                                    @endif

                                    @if(in_array($booking->status, ['pending', 'approved']) && \Carbon\Carbon::parse($booking->start_time) > now())
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            wire:click="cancelBooking({{ $booking->id }})"
                                            wire:confirm="Are you sure you want to cancel this booking?"
                                        >
                                            <svg class="w-4 h-4 text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </flux:button>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <h3 class="font-medium text-gray-900 dark:text-white mb-1">No bookings</h3>
                                    <p class="text-gray-500 dark:text-gray-400">Get started by creating your first booking.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($bookings->hasPages())
        <div class="px-6 py-4">
            <flux:pagination :paginator="$bookings" />
        </div>
        @endif
    </div>

    <!-- Mobile Card View (hidden on desktop) -->
    <div class="md:hidden sm:hidden space-y-4">
        @forelse ($bookings as $booking)
            <div id="booking-card-{{ $booking->id }}"
                 class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg p-4 shadow-sm transition-colors duration-300 {{ $highlightId == $booking->id ? 'bg-gray-100 dark:bg-zinc-700' : '' }}">
                <!-- Card Header -->
                <div class="flex items-start justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm" icon="{{ $booking->asset_type_label == 'Vehicle' ? 'car' : ($booking->asset_type_label == 'Meeting Room' ? 'building-office' : 'computer-desktop') }}" color="{{ $booking->asset_type_label == 'Vehicle' ? 'green' : ($booking->asset_type_label == 'Meeting Room' ? 'blue' : 'fuchsia') }}">
                            {{ $booking->asset_type_label }}
                        </flux:badge>
                        <span class="text-xs text-gray-500 dark:text-gray-400">#{{ $loop->iteration }}</span>
                    </div>
                    
                    <!-- Status Badge -->
                    @if ($booking->status == 'pending')
                        <flux:badge color="yellow" size="sm">
                            <flux:icon name="clock" class="w-3 h-3 mr-1" />
                            {{ ucwords($booking->status) }}
                        </flux:badge>
                    @elseif ($booking->status == 'approved')     
                        <flux:badge color="sky" size="sm">
                            <flux:icon name="check" class="w-3 h-3 mr-1" />
                            {{ ucwords($booking->status) }}
                        </flux:badge>  
                    @elseif ($booking->status == 'rejected')     
                        <flux:badge color="red" size="sm">
                            <flux:icon name="x-mark" class="w-3 h-3 mr-1" />
                            {{ ucwords($booking->status) }}
                        </flux:badge>                                                                                                
                    @elseif ($booking->status == 'cancelled')
                        <flux:badge color="zinc" size="sm">
                            <flux:icon name="arrow-turn-down-left" class="w-3 h-3 mr-1" />
                            {{ ucwords($booking->status) }}
                        </flux:badge> 
                    @elseif ($booking->status == 'done')
                        <flux:badge color="green" size="sm">
                            <flux:icon name="check-circle" class="w-3 h-3 mr-1" />
                            {{ ucwords($booking->status) }}
                        </flux:badge>
                    @endif
                </div>

                <!-- Asset Details -->
                <div class="mb-3">
                    <h3 class="font-semibold text-gray-900 dark:text-white">
                        @if ($booking->asset_type_label == 'Vehicle')
                            {{ $booking->asset?->model ?? 'N/A' }}
                            @if($booking->asset?->plate_number)
                                <span class="text-sm text-gray-500 font-normal">({{ $booking->asset->plate_number }})</span>
                            @endif
                        @else
                            {{ $booking->asset?->name ?? 'N/A' }}
                        @endif
                    </h3>
                </div>

                <!-- Date & Duration Info -->
                <div class="mb-3 text-sm">
                    @php
                        $start = \Carbon\Carbon::parse($booking->start_time);
                        $end = \Carbon\Carbon::parse($booking->end_time);
                        $totalMinutes = $start->diffInMinutes($end);
                        $totalHours = floor($totalMinutes / 60);
                        $days = (int) $start->diffInDays($end);
                        
                        // Enhanced date display logic
                        if ($days > 1) {
                            // Multi-day booking
                            if ($start->month === $end->month) {
                                // Same month: "Aug 14 - 17, 2025"
                                $dateDisplay = $start->format('M j') . ' - ' . $end->format('j, Y');
                            } else {
                                // Different months: "Aug 29 - Sept 2, 2025"
                                $dateDisplay = $start->format('M j') . ' - ' . $end->format('M j, Y');
                            }
                        } else {
                            // Same day booking
                            $dateDisplay = $start->format('M d, Y');
                        }
                        
                        // Duration calculation
                        if ($totalMinutes < 60) {
                            $durationText = $totalMinutes . 'm';
                        } elseif ($totalHours < 24) {
                            $hours = $totalHours;
                            $minutes = $totalMinutes % 60;
                            $durationText = $hours . 'h' . ($minutes > 0 ? ' ' . $minutes . 'm' : '');
                        } else {
                            $hours = $totalHours % 24;
                            $durationText = $days . 'd' . ($hours > 0 ? ' ' . $hours . 'h' : '');
                        }
                    @endphp

                    <div class="flex items-center gap-1 text-gray-900 dark:text-white mb-1">
                        <flux:icon name="calendar" class="w-4 h-4 text-gray-500" />
                        <span class="font-medium">{{ $dateDisplay }}</span>
                    </div>
                    
                    @if($days === 0)
                        {{-- Single day booking - show time range --}}
                        <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400 ml-5">
                            <span class="text-sm">{{ $start->format('h:i A') }} - {{ $end->format('h:i A') }} ({{ $durationText }})</span>
                        </div>
                    @else
                        {{-- Multi-day booking - show start and end times --}}
                        <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400 ml-5">
                            <span class="text-sm">{{ $start->format('h:i A') }} → {{ $end->format('h:i A') }} ({{ $durationText }})</span>
                        </div>
                    @endif
                </div>

                <!-- Booking Created Info -->
                <div class="mb-4">
                    <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                        <flux:icon name="clock" class="w-3 h-3" />
                        <span>Booked {{ \Carbon\Carbon::parse($booking->created_at)->diffForHumans() }}</span>
                    </div>
                    <div class="text-xs text-gray-400 ml-4">
                        {{ \Carbon\Carbon::parse($booking->created_at)->format('M d, h:i A') }}
                    </div>
                </div>

                <!-- Status Details & Urgency -->
                <div class="mb-4">
                    @if ($booking->status == 'pending')
                        <div class="text-xs text-yellow-600 dark:text-yellow-400">Awaiting approval</div>
                    @elseif ($booking->status == 'approved')
                        @if(\Carbon\Carbon::parse($booking->start_time)->isToday())
                            <flux:badge color="orange" size="sm">
                                <flux:icon name="exclamation-triangle" class="w-3 h-3 mr-1" />
                                Starts Today
                            </flux:badge>
                        @elseif(\Carbon\Carbon::parse($booking->start_time)->isTomorrow())
                            <flux:badge color="blue" size="sm">
                                <flux:icon name="clock" class="w-3 h-3 mr-1" />
                                Starts Tomorrow
                            </flux:badge>
                        @endif
                    @elseif ($booking->status == 'rejected')
                        <div class="text-xs text-red-600 dark:text-red-400">Request denied</div>
                    @elseif ($booking->status == 'cancelled')
                        <div class="text-xs text-gray-500 dark:text-gray-400">You cancelled this</div>
                    @elseif ($booking->status == 'done')
                        <div class="text-xs text-green-600 dark:text-green-400">Booking completed</div>
                    @endif
                </div>

                <!-- Card Actions -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-neutral-700">
                    <flux:button size="sm" href="{{ route('bookings.show.user', ['id' => $booking->id, 'page' => $bookings->currentPage()]) }}" variant="ghost">
                        <flux:icon name="eye" class="w-4 h-4 mr-1" />
                        View More
                    </flux:button>
                    
                    <div class="flex gap-2">
                        <!-- Edit Button - Only for pending bookings that haven't started -->
                        @if($booking->status == 'pending' && \Carbon\Carbon::parse($booking->start_time) > now())
                            @can('book.edit')
                            <flux:button size="sm" href="{{ route('bookings.edit.user', $booking->id) }}" variant="ghost">
                                <flux:icon name="pencil" class="w-4 h-4" />
                            </flux:button>
                            @endcan
                        @endif
                        
                        <!-- Cancel Button - Only for pending/approved bookings that haven't started -->
                        @if(in_array($booking->status, ['pending', 'approved']) && \Carbon\Carbon::parse($booking->start_time) > now())
                            <flux:button 
                                size="sm" 
                                variant="ghost"
                                wire:click="cancelBooking({{ $booking->id }})"
                                wire:confirm="Are you sure you want to cancel this booking?"
                                class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                            >
                                <flux:icon name="x-mark" class="w-4 h-4" />
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-lg p-8 text-center">
                <div class="flex flex-col items-center gap-2">
                    <flux:icon name="calendar-days" class="w-8 h-8 text-gray-400" />
                    <div class="text-gray-500">No bookings found</div>
                    @can('book.create')
                    <flux:button size="sm" href="{{ route('bookings.create') }}" variant="ghost" class="mt-2">
                        Create first booking
                    </flux:button>
                    @endcan
                </div>
            </div>
        @endforelse

        <!-- Mobile Pagination -->
        @if($bookings->hasPages())
        <div class="pt-4">
            <flux:pagination :paginator="$bookings" />
        </div>
        @endif
    </div>    
</div>