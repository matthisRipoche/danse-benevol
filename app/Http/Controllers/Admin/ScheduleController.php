<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEventDayRequest;
use App\Http\Requests\Admin\StoreTimeSlotRequest;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\VolunteerAssignment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * List the active edition's days and their time slots.
     */
    public function index(): View
    {
        $edition = Edition::active();

        $days = EventDay::where('edition_id', $edition->id)
            ->with(['timeSlots' => fn ($query) => $query->orderBy('position')->withCount('volunteerAssignments')])
            ->orderBy('date')
            ->get();

        return view('admin.schedule.index', [
            'edition' => $edition,
            'days' => $days,
            'missionCount' => Mission::where('edition_id', $edition->id)->count(),
        ]);
    }

    /**
     * Add a day to the active edition.
     */
    public function storeDay(StoreEventDayRequest $request): RedirectResponse
    {
        $date = Carbon::parse($request->validated('date'));

        $day = EventDay::create([
            'edition_id' => Edition::active()->id,
            'date' => $date,
            'label' => EventDay::labelFor($date),
        ]);

        AuditLog::record($request->user(), 'event_day.created', $day, ['date' => $date->format('Y-m-d')]);

        return redirect()->route('admin.schedule.index')->with('status', "{$day->label} {$date->format('d/m/Y')} ajouté.");
    }

    /**
     * Delete a day and its time slots, unless volunteers are already booked on it.
     */
    public function destroyDay(Request $request, EventDay $eventDay): RedirectResponse
    {
        $this->abortUnlessActiveEdition($eventDay);

        $bookedCount = VolunteerAssignment::whereIn('time_slot_id', $eventDay->timeSlots()->select('id'))->count();

        if ($bookedCount > 0) {
            return back()->with('error', "Impossible de supprimer ce jour : {$bookedCount} réservation(s) de bénévoles dessus.");
        }

        AuditLog::record($request->user(), 'event_day.deleted', $eventDay, ['date' => $eventDay->date->format('Y-m-d')]);
        $eventDay->delete();

        return redirect()->route('admin.schedule.index')->with('status', 'Jour supprimé avec ses créneaux.');
    }

    /**
     * Add a time slot to a day, and open it for every mission with its default capacity.
     */
    public function storeTimeSlot(StoreTimeSlotRequest $request, EventDay $eventDay): RedirectResponse
    {
        $this->abortUnlessActiveEdition($eventDay);

        $timeSlot = DB::transaction(function () use ($request, $eventDay) {
            $timeSlot = $eventDay->timeSlots()->create([
                'starts_at' => $request->validated('starts_at').':00',
                'ends_at' => $request->validated('ends_at').':00',
                'position' => 200,
            ]);

            $eventDay->renumberTimeSlots();

            Mission::where('edition_id', $eventDay->edition_id)
                ->where('default_capacity', '>', 0)
                ->get()
                ->each(fn (Mission $mission) => MissionSlot::create([
                    'mission_id' => $mission->id,
                    'time_slot_id' => $timeSlot->id,
                    'capacity' => $mission->default_capacity,
                ]));

            return $timeSlot;
        });

        AuditLog::record($request->user(), 'time_slot.created', $timeSlot, [
            'starts_at' => $timeSlot->starts_at,
            'ends_at' => $timeSlot->ends_at,
        ]);

        return redirect()->route('admin.schedule.index')
            ->with('status', "Créneau {$request->validated('starts_at')} – {$request->validated('ends_at')} ajouté au {$eventDay->label}.");
    }

    /**
     * Delete a time slot, unless volunteers are already booked on it.
     */
    public function destroyTimeSlot(Request $request, TimeSlot $timeSlot): RedirectResponse
    {
        $eventDay = $timeSlot->eventDay;
        $this->abortUnlessActiveEdition($eventDay);

        $bookedCount = $timeSlot->volunteerAssignments()->count();

        if ($bookedCount > 0) {
            return back()->with('error', "Impossible de supprimer ce créneau : {$bookedCount} réservation(s) de bénévoles dessus.");
        }

        AuditLog::record($request->user(), 'time_slot.deleted', $timeSlot, [
            'starts_at' => $timeSlot->starts_at,
            'ends_at' => $timeSlot->ends_at,
        ]);

        DB::transaction(function () use ($timeSlot, $eventDay) {
            $timeSlot->delete();
            $eventDay->renumberTimeSlots();
        });

        return redirect()->route('admin.schedule.index')->with('status', 'Créneau supprimé.');
    }

    /**
     * Days of past or other editions are not managed from here.
     */
    private function abortUnlessActiveEdition(EventDay $eventDay): void
    {
        abort_unless($eventDay->edition_id === Edition::active()->id, 404);
    }
}
