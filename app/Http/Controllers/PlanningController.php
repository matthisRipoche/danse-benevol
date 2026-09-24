<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\EditionVolunteer;
use App\Models\EventDay;
use App\Models\MissionSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlanningController extends Controller
{
    /**
     * Display the volunteer's planning for the active edition.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $edition = Edition::active();
        $editionVolunteer = $this->editionVolunteerOrAbort($user, $edition);

        $days = EventDay::where('edition_id', $edition->id)
            ->with(['timeSlots' => function ($query) {
                $query->orderBy('position')->with(['missionSlots' => function ($query) {
                    $query->whereHas('mission', fn ($q) => $q->where('is_public', true))
                        ->with('mission');
                }]);
            }])
            ->orderBy('date')
            ->get();

        $assignments = $this->assignmentsFor($user, $edition);

        return view('planning.index', [
            'edition' => $edition,
            'days' => $days,
            'assignments' => $assignments,
            'reservedMissionSlotIds' => $assignments->pluck('mission_slot_id')->all(),
            'reservedTimeSlotIds' => $assignments->pluck('time_slot_id')->all(),
            'isValidated' => (bool) $editionVolunteer->pivot->is_validated,
            'isAwaitingMinorValidation' => $user->is_minor && ! $user->minor_validated_at,
            'isMinor' => $user->is_minor,
        ]);
    }

    /**
     * Reserve a mission slot for the current volunteer, as a draft.
     */
    public function reserve(Request $request, MissionSlot $missionSlot): RedirectResponse
    {
        $user = $request->user();
        $edition = Edition::active();
        $editionVolunteer = $this->editionVolunteerOrAbort($user, $edition);

        if ($editionVolunteer->pivot->is_validated) {
            return back()->with('error', 'Ton planning est validé, il ne peut plus être modifié.');
        }

        $missionSlot->loadMissing('timeSlot.eventDay', 'mission');

        if ($missionSlot->timeSlot->eventDay->edition_id !== $edition->id || ! $missionSlot->mission->is_public) {
            abort(404);
        }

        if ($missionSlot->mission->is_adult_only && $user->is_minor) {
            return back()->with('error', 'Cette mission est interdite aux mineurs.');
        }

        return DB::transaction(function () use ($user, $edition, $missionSlot) {
            // Both rows are locked so that simultaneous requests are checked one after the other:
            // the volunteer's registration guards their slot quota, the mission slot its capacity.
            $lockedEditionVolunteer = $this->lockedEditionVolunteer($user, $edition);
            $lockedMissionSlot = MissionSlot::whereKey($missionSlot->id)->lockForUpdate()->firstOrFail();

            if ($lockedEditionVolunteer->is_validated) {
                return back()->with('error', 'Ton planning est validé, il ne peut plus être modifié.');
            }

            $assignments = $this->assignmentsFor($user, $edition);

            if ($assignments->contains('mission_slot_id', $missionSlot->id)) {
                return back()->with('error', 'Tu as déjà réservé ce créneau.');
            }

            if ($assignments->contains('time_slot_id', $missionSlot->time_slot_id)) {
                return back()->with('error', 'Tu as déjà une mission sur ce créneau horaire.');
            }

            if ($assignments->count() >= $edition->max_slots_per_volunteer) {
                return back()->with('error', "Maximum {$edition->max_slots_per_volunteer} créneaux par bénévole.");
            }

            if ($lockedMissionSlot->remainingCapacity() <= 0) {
                return back()->with('error', 'Ce créneau est complet.');
            }

            $positionsForDay = $assignments
                ->where('missionSlot.timeSlot.event_day_id', $missionSlot->timeSlot->event_day_id)
                ->pluck('missionSlot.timeSlot.position')
                ->push($missionSlot->timeSlot->position)
                ->all();

            if ($this->longestConsecutiveRun($positionsForDay) > $edition->max_consecutive_slots) {
                return back()->with('error', "Pas plus de {$edition->max_consecutive_slots} créneaux consécutifs le même jour.");
            }

            VolunteerAssignment::create([
                'user_id' => $user->id,
                'mission_slot_id' => $lockedMissionSlot->id,
                'time_slot_id' => $missionSlot->time_slot_id,
                'status' => 'draft',
            ]);

            return back()->with('status', 'Créneau réservé.');
        });
    }

    /**
     * Cancel a draft reservation for the current volunteer.
     */
    public function cancel(Request $request, MissionSlot $missionSlot): RedirectResponse
    {
        $user = $request->user();
        $edition = Edition::active();
        $editionVolunteer = $this->editionVolunteerOrAbort($user, $edition);

        if ($editionVolunteer->pivot->is_validated) {
            return back()->with('error', 'Ton planning est validé, il ne peut plus être modifié.');
        }

        $assignment = VolunteerAssignment::where('user_id', $user->id)
            ->where('mission_slot_id', $missionSlot->id)
            ->first();

        if (! $assignment) {
            return back()->with('error', 'Aucune réservation à annuler pour ce créneau.');
        }

        $assignment->delete();

        return back()->with('status', 'Créneau annulé.');
    }

    /**
     * Validate the volunteer's planning definitively, locking it.
     */
    public function finalize(Request $request): RedirectResponse
    {
        $user = $request->user();
        $edition = Edition::active();
        $editionVolunteer = $this->editionVolunteerOrAbort($user, $edition);

        if ($editionVolunteer->pivot->is_validated) {
            return back()->with('error', 'Ton planning est déjà validé.');
        }

        if ($user->is_minor && ! $user->minor_validated_at) {
            return back()->with('error', "Ton profil mineur doit d'abord être validé par l'organisation avant que tu puisses valider ton planning.");
        }

        return DB::transaction(function () use ($user, $edition) {
            $lockedEditionVolunteer = $this->lockedEditionVolunteer($user, $edition);

            if ($lockedEditionVolunteer->is_validated) {
                return back()->with('error', 'Ton planning est déjà validé.');
            }

            if ($this->assignmentsFor($user, $edition)->count() < $edition->min_slots_per_volunteer) {
                return back()->with('error', "Réserve au moins {$edition->min_slots_per_volunteer} créneau(x) avant de valider définitivement.");
            }

            VolunteerAssignment::where('user_id', $user->id)
                ->whereHas('missionSlot.timeSlot.eventDay', fn ($q) => $q->where('edition_id', $edition->id))
                ->update(['status' => 'validated']);

            $user->editions()->updateExistingPivot($edition->id, [
                'is_validated' => true,
                'validated_at' => now(),
                'badge_uid' => (string) Str::uuid(),
            ]);

            $user->forceFill(['profile_locked_at' => now()])->save();

            return redirect()->route('planning.index')->with('status', 'Planning validé et verrouillé.');
        });
    }

    /**
     * Resolve the active edition's pivot for this volunteer, aborting if they aren't registered for it.
     */
    private function editionVolunteerOrAbort(User $user, Edition $edition): Edition
    {
        $editionVolunteer = $user->editions()->where('editions.id', $edition->id)->first();

        abort_if(! $editionVolunteer, 403, "Tu n'es pas inscrit à cette édition.");

        return $editionVolunteer;
    }

    /**
     * Lock the volunteer's registration row for the edition until the end of the current transaction.
     */
    private function lockedEditionVolunteer(User $user, Edition $edition): EditionVolunteer
    {
        return EditionVolunteer::where('user_id', $user->id)
            ->where('edition_id', $edition->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * The current volunteer's assignments for the given edition, with their mission slot's day/time loaded.
     *
     * @return Collection<int, VolunteerAssignment>
     */
    private function assignmentsFor(User $user, Edition $edition)
    {
        return VolunteerAssignment::where('user_id', $user->id)
            ->whereHas('missionSlot.timeSlot.eventDay', fn ($q) => $q->where('edition_id', $edition->id))
            ->with('missionSlot.mission', 'missionSlot.timeSlot.eventDay')
            ->get();
    }

    /**
     * The length of the longest run of consecutive integers in the given positions.
     *
     * @param  array<int, int>  $positions
     */
    private function longestConsecutiveRun(array $positions): int
    {
        sort($positions);

        $longest = 0;
        $current = 0;
        $previous = null;

        foreach ($positions as $position) {
            $current = ($previous !== null && $position === $previous + 1) ? $current + 1 : 1;
            $longest = max($longest, $current);
            $previous = $position;
        }

        return $longest;
    }
}
