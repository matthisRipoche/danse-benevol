<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignRestrictedMissionRequest;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\EventDay;
use App\Models\MissionSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RestrictedMissionController extends Controller
{
    /**
     * List the restricted mission slots and their current assignments for the active edition.
     */
    public function index(): View
    {
        $edition = Edition::active();

        $days = EventDay::where('edition_id', $edition->id)
            ->with(['timeSlots' => function ($query) {
                $query->orderBy('position')->with(['missionSlots' => function ($query) {
                    $query->whereHas('mission', fn ($q) => $q->where('is_public', false))
                        ->with('mission', 'volunteerAssignments.user');
                }]);
            }])
            ->orderBy('date')
            ->get();

        return view('admin.restricted-missions.index', [
            'edition' => $edition,
            'days' => $days,
        ]);
    }

    /**
     * Force-assign a volunteer to a restricted mission slot.
     */
    public function assign(AssignRestrictedMissionRequest $request, MissionSlot $missionSlot): RedirectResponse
    {
        $edition = Edition::active();
        $missionSlot->loadMissing('timeSlot.eventDay', 'mission');

        if ($missionSlot->mission->is_public || $missionSlot->timeSlot->eventDay->edition_id !== $edition->id) {
            abort(404);
        }

        return DB::transaction(function () use ($request, $missionSlot) {
            $lockedMissionSlot = MissionSlot::whereKey($missionSlot->id)->lockForUpdate()->firstOrFail();

            $volunteer = User::where('email', $request->validated('email'))->firstOrFail();

            if (VolunteerAssignment::where('user_id', $volunteer->id)->where('time_slot_id', $missionSlot->time_slot_id)->exists()) {
                return back()->with('error', 'Ce bénévole a déjà une mission sur ce créneau horaire.');
            }

            if ($lockedMissionSlot->remainingCapacity() <= 0) {
                return back()->with('error', 'Ce poste est complet.');
            }

            $assignment = VolunteerAssignment::create([
                'user_id' => $volunteer->id,
                'mission_slot_id' => $lockedMissionSlot->id,
                'time_slot_id' => $missionSlot->time_slot_id,
                'status' => 'validated',
                'assigned_by_id' => $request->user()->id,
            ]);

            AuditLog::record($request->user(), 'volunteer_assignment.forced', $assignment, [
                'user_id' => $volunteer->id,
                'mission' => $missionSlot->mission->name,
            ]);

            return back()->with('status', "{$volunteer->first_name} {$volunteer->last_name} a été assigné(e).");
        });
    }

    /**
     * Remove a volunteer's assignment to a restricted mission slot.
     */
    public function unassign(Request $request, VolunteerAssignment $volunteerAssignment): RedirectResponse
    {
        $edition = Edition::active();
        $volunteerAssignment->loadMissing('missionSlot.mission', 'missionSlot.timeSlot.eventDay');

        $missionSlot = $volunteerAssignment->missionSlot;

        if ($missionSlot->mission->is_public || $missionSlot->timeSlot->eventDay->edition_id !== $edition->id) {
            abort(404);
        }

        AuditLog::record($request->user(), 'volunteer_assignment.unassigned', $volunteerAssignment, [
            'user_id' => $volunteerAssignment->user_id,
            'mission' => $missionSlot->mission->name,
        ]);

        $volunteerAssignment->delete();

        return back()->with('status', 'Assignation retirée.');
    }
}
