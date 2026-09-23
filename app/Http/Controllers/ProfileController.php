<?php

namespace App\Http\Controllers;

use App\Models\Edition;
use App\Models\VolunteerAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /**
     * Display the volunteer's profile, badge and validated route sheet for the active edition.
     */
    public function show(Request $request): View
    {
        $user = $request->user();
        $edition = Edition::active();
        $editionVolunteer = $user->editions()->where('editions.id', $edition->id)->first();

        abort_if(! $editionVolunteer, 403, "Tu n'es pas inscrit à cette édition.");

        $assignments = VolunteerAssignment::where('user_id', $user->id)
            ->whereHas('missionSlot.timeSlot.eventDay', fn ($q) => $q->where('edition_id', $edition->id))
            ->with('missionSlot.mission', 'missionSlot.timeSlot.eventDay')
            ->get()
            ->sortBy(fn (VolunteerAssignment $assignment) => [
                $assignment->missionSlot->timeSlot->eventDay->date,
                $assignment->missionSlot->timeSlot->starts_at,
            ])
            ->values();

        return view('profile.show', [
            'user' => $user,
            'edition' => $edition,
            'assignments' => $assignments,
            'totalMinutes' => $assignments->sum(fn (VolunteerAssignment $assignment) => $assignment->missionSlot->timeSlot->durationInMinutes()),
            'isValidated' => (bool) $editionVolunteer->pivot->is_validated,
            'badgeUid' => $editionVolunteer->pivot->badge_uid,
        ]);
    }

    /**
     * Stream the current volunteer's own badge photo from the private disk.
     */
    public function photo(Request $request): StreamedResponse
    {
        $photoPath = $request->user()->photo_path;

        abort_if(! $photoPath || ! Storage::disk('local')->exists($photoPath), 404);

        return Storage::disk('local')->response($photoPath);
    }
}
