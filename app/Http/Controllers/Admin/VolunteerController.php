<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VolunteerController extends Controller
{
    /**
     * Search and list volunteers registered for the active edition.
     */
    public function index(Request $request): View
    {
        $edition = Edition::active();

        // Built as an explicitly mutated variable, not a `when()` chain: `when()` forwards
        // through the relation's underlying query builder, which loses `wherePivot()`.
        $query = $edition->volunteers();

        if ($request->filled('nom')) {
            $query->where('last_name', 'like', '%'.$request->string('nom').'%');
        }

        if ($request->filled('prenom')) {
            $query->where('first_name', 'like', '%'.$request->string('prenom').'%');
        }

        if ($request->filled('statut')) {
            $query->wherePivot('is_validated', '=', $request->input('statut') === 'valide');
        }

        if ($request->filled('mission')) {
            $query->whereHas(
                'volunteerAssignments.missionSlot.mission',
                fn ($m) => $m->where('missions.id', $request->integer('mission'))
            );
        }

        if ($request->filled('jour')) {
            $query->whereHas(
                'volunteerAssignments.missionSlot.timeSlot.eventDay',
                fn ($d) => $d->where('event_days.id', $request->integer('jour'))
            );
        }

        $volunteers = $query
            ->with(['volunteerAssignments' => function ($q) use ($edition) {
                $q->whereHas('missionSlot.timeSlot.eventDay', fn ($d) => $d->where('edition_id', $edition->id))
                    ->with('missionSlot.mission', 'missionSlot.timeSlot.eventDay');
            }])
            ->orderBy('last_name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.volunteers.index', [
            'edition' => $edition,
            'volunteers' => $volunteers,
            'missions' => Mission::where('edition_id', $edition->id)->orderBy('name')->get(),
            'days' => EventDay::where('edition_id', $edition->id)->orderBy('date')->get(),
        ]);
    }

    /**
     * Show a volunteer's profile and planning for the active edition.
     */
    public function show(User $user): View
    {
        $edition = Edition::active();
        $editionVolunteer = $user->editions()->where('editions.id', $edition->id)->first();

        abort_if(! $editionVolunteer, 404);

        $assignments = VolunteerAssignment::where('user_id', $user->id)
            ->whereHas('missionSlot.timeSlot.eventDay', fn ($q) => $q->where('edition_id', $edition->id))
            ->with('missionSlot.mission', 'missionSlot.timeSlot.eventDay')
            ->get()
            ->sortBy(fn (VolunteerAssignment $assignment) => [
                $assignment->missionSlot->timeSlot->eventDay->date,
                $assignment->missionSlot->timeSlot->starts_at,
            ])
            ->values();

        return view('admin.volunteers.show', [
            'edition' => $edition,
            'volunteer' => $user->load('usedInvitationCode'),
            'assignmentsByDay' => $assignments->groupBy(fn (VolunteerAssignment $assignment) => $assignment->missionSlot->timeSlot->event_day_id),
            'assignmentCount' => $assignments->count(),
            'totalMinutes' => $assignments->sum(fn (VolunteerAssignment $assignment) => $assignment->missionSlot->timeSlot->durationInMinutes()),
            'isValidated' => (bool) $editionVolunteer->pivot->is_validated,
            'validatedAt' => $editionVolunteer->pivot->validated_at ? Carbon::parse($editionVolunteer->pivot->validated_at) : null,
            'badgeUid' => $editionVolunteer->pivot->badge_uid,
        ]);
    }

    /**
     * Stream a volunteer's badge photo from the private disk.
     */
    public function photo(User $user): StreamedResponse
    {
        abort_if(! $user->photo_path || ! Storage::disk('local')->exists($user->photo_path), 404);

        return Storage::disk('local')->response($user->photo_path);
    }
}
