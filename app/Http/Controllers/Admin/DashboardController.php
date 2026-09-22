<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\MissionSlot;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard for the active edition.
     */
    public function index(): View
    {
        $edition = Edition::active();

        $invitationCodeCounts = $edition->invitationCodes()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalVolunteers = $edition->volunteers()->count();
        $validatedVolunteers = $edition->volunteers()->wherePivot('is_validated', true)->count();

        $missionSlots = MissionSlot::whereHas('timeSlot.eventDay', fn ($q) => $q->where('edition_id', $edition->id))
            ->with(['mission', 'timeSlot.eventDay'])
            ->withCount('volunteerAssignments')
            ->get();

        $totalCapacity = $missionSlots->sum('capacity');
        $totalFilled = $missionSlots->sum('volunteer_assignments_count');

        $fillRateByDay = $missionSlots
            ->groupBy(fn (MissionSlot $slot) => $slot->timeSlot->eventDay->id)
            ->map(function ($slots) {
                $day = $slots->first()->timeSlot->eventDay;
                $row = $this->fillRateRow($day->label, $slots);
                $row->date = $day->date;

                return $row;
            })
            ->sortBy('date')
            ->values();

        $fillRateByMission = $missionSlots
            ->groupBy('mission_id')
            ->map(fn ($slots) => $this->fillRateRow($slots->first()->mission->name, $slots))
            ->sortBy('label')
            ->values();

        return view('admin.dashboard', [
            'edition' => $edition,
            'invitationCodeCounts' => $invitationCodeCounts,
            'totalInvitationCodes' => $invitationCodeCounts->sum(),
            'totalVolunteers' => $totalVolunteers,
            'validatedVolunteers' => $validatedVolunteers,
            'pendingVolunteers' => $totalVolunteers - $validatedVolunteers,
            'totalCapacity' => $totalCapacity,
            'totalFilled' => $totalFilled,
            'globalFillRate' => $this->rate($totalFilled, $totalCapacity),
            'fillRateByDay' => $fillRateByDay,
            'fillRateByMission' => $fillRateByMission,
        ]);
    }

    /**
     * Build a fill-rate summary row (label, capacity, filled, rate) for a group of mission slots.
     *
     * @param  Collection<int, MissionSlot>  $slots
     */
    private function fillRateRow(string $label, $slots): object
    {
        $capacity = $slots->sum('capacity');
        $filled = $slots->sum('volunteer_assignments_count');

        return (object) [
            'label' => $label,
            'capacity' => $capacity,
            'filled' => $filled,
            'rate' => $this->rate($filled, $capacity),
        ];
    }

    /**
     * A filled/capacity percentage, safe against division by zero.
     */
    private function rate(int $filled, int $capacity): int
    {
        return $capacity > 0 ? (int) round($filled / $capacity * 100) : 0;
    }
}
