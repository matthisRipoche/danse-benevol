<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
}
