<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\EditionVolunteer;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
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

        if ($request->input('mineur') === 'a_valider') {
            $query->where('is_minor', true)->whereNull('minor_validated_at');
        } elseif ($request->input('mineur') === 'valide') {
            $query->where('is_minor', true)->whereNotNull('minor_validated_at');
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
     * Resolve a scanned badge QR code to the volunteer's detail page.
     */
    public function badge(string $badgeUid): RedirectResponse
    {
        $editionVolunteer = EditionVolunteer::where('badge_uid', $badgeUid)->firstOrFail();

        return redirect()->route('admin.volunteers.show', $editionVolunteer->user_id);
    }

    /**
     * Stream a volunteer's badge photo from the private disk.
     */
    public function photo(User $user): StreamedResponse
    {
        abort_if(! $user->photo_path || ! Storage::disk('local')->exists($user->photo_path), 404);

        return Storage::disk('local')->response($user->photo_path);
    }

    /**
     * Validate a minor volunteer's profile.
     */
    public function validateMinor(Request $request, User $user): RedirectResponse
    {
        $edition = Edition::active();

        abort_unless($user->editions()->where('editions.id', $edition->id)->exists(), 404);

        if (! $user->is_minor) {
            return back()->with('error', "Ce bénévole n'est pas déclaré mineur.");
        }

        if ($user->minor_validated_at) {
            return back()->with('error', 'Ce profil est déjà validé.');
        }

        $user->forceFill(['minor_validated_at' => now()])->save();

        AuditLog::record($request->user(), 'user.minor_validated', $user);

        return back()->with('status', "Profil de {$user->first_name} {$user->last_name} validé.");
    }
}
