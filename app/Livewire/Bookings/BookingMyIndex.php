<?php

namespace App\Livewire\Bookings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Booking;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class BookingMyIndex extends Component
{
    use AuthorizesRequests, WithPagination;

    public $selectedBookingId = null;
    public $statusFilter = '';
    public $assetTypeFilter = '';
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $highlightId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'assetTypeFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'highlightId' => ['except' => null],
    ];

    public function render()
    {
        $bookings = $this->getBookingsQuery();

        $overdueBookings = Booking::with('asset')
            ->where('booked_by', auth()->id())
            ->where('status', 'approved')
            ->where('end_time', '<', now())
            ->orderBy('end_time', 'asc')
            ->get();

        return view('livewire.bookings.booking-my-index', compact('bookings', 'overdueBookings'));
    }

    private function getBookingsQuery()
    {
        $query = Booking::with(['user', 'asset'])
                        ->where('booked_by', auth()->id()); // Only user's own bookings

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                // Search by booking ID
                $q->where('id', 'like', '%' . $this->search . '%')
                // Search in vehicles (has model and plate_number, no name)
                ->orWhere(function ($assetQuery) {
                    $assetQuery->where('asset_type', 'vehicle')
                               ->whereHasMorph('asset', 'App\Models\Vehicle', function ($vehicleQuery) {
                                   $vehicleQuery->where('model', 'like', '%' . $this->search . '%')
                                              ->orWhere('plate_number', 'like', '%' . $this->search . '%');
                               });
                })
                // Search in meeting rooms (has name, no model or plate_number)
                ->orWhere(function ($assetQuery) {
                    $assetQuery->where('asset_type', 'meeting_room')
                               ->whereHasMorph('asset', 'App\Models\MeetingRoom', function ($roomQuery) {
                                   $roomQuery->where('name', 'like', '%' . $this->search . '%')
                                           ->orWhere('location', 'like', '%' . $this->search . '%');
                               });
                })
                // Search in IT assets (has name and asset_tag, no model or plate_number)
                ->orWhere(function ($assetQuery) {
                    $assetQuery->where('asset_type', 'it_asset')
                               ->whereHasMorph('asset', 'App\Models\ItAsset', function ($itQuery) {
                                   $itQuery->where('name', 'like', '%' . $this->search . '%')
                                          ->orWhere('asset_tag', 'like', '%' . $this->search . '%')
                                          ->orWhere('location', 'like', '%' . $this->search . '%');
                               });
                });
            });
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        // Apply asset type filter
        if (!empty($this->assetTypeFilter)) {
            // Map the filter values to actual asset_type values
            $assetTypeMap = [
                'Vehicle' => 'vehicle',
                'Room' => 'meeting_room',
                'Equipment' => 'it_asset'
            ];
            
            $actualAssetType = $assetTypeMap[$this->assetTypeFilter] ?? strtolower($this->assetTypeFilter);
            $query->where('asset_type', $actualAssetType);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Return paginated results
        return $query->paginate(15);
    }

    public function cancelBooking($bookingId)
    {
        try {
            $booking = Booking::where('id', $bookingId)
                             ->where('booked_by', auth()->id()) // Ensure user owns this booking
                             ->firstOrFail();

            // Only allow cancellation of pending or approved bookings
            if (!in_array($booking->status, ['pending', 'approved'])) {
                session()->flash('error', 'You can only cancel pending or approved bookings');
                return;
            }

            // Don't allow cancellation if booking has already started
            if ($booking->start_time <= now()) {
                session()->flash('error', 'Cannot cancel bookings that have already started');
                return;
            }

            // Update the status
            $booking->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);

            session()->flash('success', 'Booking has been cancelled successfully');

        } catch (\Exception $e) {
            session()->flash('error', 'An error occurred while cancelling the booking');
            
            \Log::error('Booking cancellation error: ' . $e->getMessage(), [
                'booking_id' => $bookingId,
                'user_id' => auth()->id(),
            ]);
        }
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
        
        // Reset to first page when sorting changes
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingAssetTypeFilter()
    {
        $this->resetPage();
    }

    /**
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->assetTypeFilter = '';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }
}