<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportMissionsRequest;
use App\Http\Requests\Admin\StoreMissionRequest;
use App\Http\Requests\Admin\UpdateMissionRequest;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\EventDay;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Support\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MissionController extends Controller
{
    /**
     * Maximum number of mission lines accepted in a single import.
     */
    private const int MAX_IMPORT_ROWS = 200;

    /**
     * List the active edition's missions.
     */
    public function index(): View
    {
        $edition = Edition::active();

        $missions = Mission::where('edition_id', $edition->id)
            ->with(['missionSlots' => fn ($query) => $query->withCount('volunteerAssignments')])
            ->orderByDesc('is_public')
            ->orderBy('name')
            ->get();

        return view('admin.missions.index', [
            'edition' => $edition,
            'missions' => $missions,
            'timeSlotCount' => $this->timeSlotsFor($edition)->count(),
        ]);
    }

    /**
     * Display the mission creation form.
     */
    public function create(): View
    {
        return view('admin.missions.create', [
            'timeSlotCount' => $this->timeSlotsFor(Edition::active())->count(),
        ]);
    }

    /**
     * Create a mission, open on every time slot of the edition with its default capacity.
     */
    public function store(StoreMissionRequest $request): RedirectResponse
    {
        $mission = $this->createMission($request->validated(), $request->user(), Edition::active());

        return redirect()->route('admin.missions.index')->with('status', "Mission « {$mission->name} » créée.");
    }

    /**
     * Display the mission edition form, with the capacity of each time slot.
     */
    public function edit(Mission $mission): View
    {
        $this->abortUnlessActiveEdition($mission);

        $missionSlots = $mission->missionSlots()->withCount('volunteerAssignments')->get()->keyBy('time_slot_id');

        return view('admin.missions.edit', [
            'mission' => $mission,
            'days' => $this->daysFor($mission->edition),
            'missionSlots' => $missionSlots,
        ]);
    }

    /**
     * Update a mission and the capacity of each of its time slots (0 closes the time slot).
     */
    public function update(UpdateMissionRequest $request, Mission $mission): RedirectResponse
    {
        $this->abortUnlessActiveEdition($mission);

        $timeSlotIds = $this->timeSlotsFor($mission->edition)->pluck('id');
        $missionSlots = $mission->missionSlots()->withCount('volunteerAssignments')->get()->keyBy('time_slot_id');
        $capacities = collect($request->validated('capacities', []))
            ->only($timeSlotIds->all())
            ->map(fn ($capacity) => (int) $capacity);

        $errors = $capacities
            ->filter(fn (int $capacity, int $timeSlotId) => $capacity < ($missionSlots->get($timeSlotId)?->volunteer_assignments_count ?? 0))
            ->mapWithKeys(fn (int $capacity, int $timeSlotId) => [
                "capacities.{$timeSlotId}" => "{$missionSlots->get($timeSlotId)->volunteer_assignments_count} bénévole(s) déjà inscrit(s) sur ce créneau : impossible de descendre à {$capacity} place(s).",
            ]);

        if ($errors->isNotEmpty()) {
            throw ValidationException::withMessages($errors->all());
        }

        DB::transaction(function () use ($request, $mission, $capacities, $missionSlots) {
            $mission->update($request->safe()->only(['name', 'description', 'is_public', 'default_capacity']));

            foreach ($capacities as $timeSlotId => $capacity) {
                $missionSlot = $missionSlots->get($timeSlotId);

                if ($capacity === 0) {
                    $missionSlot?->delete();
                } elseif ($missionSlot) {
                    $missionSlot->update(['capacity' => $capacity]);
                } else {
                    MissionSlot::create(['mission_id' => $mission->id, 'time_slot_id' => $timeSlotId, 'capacity' => $capacity]);
                }
            }
        });

        AuditLog::record($request->user(), 'mission.updated', $mission, ['name' => $mission->name]);

        return redirect()->route('admin.missions.index')->with('status', "Mission « {$mission->name} » mise à jour.");
    }

    /**
     * Delete a mission, unless volunteers are already booked on it.
     */
    public function destroy(Request $request, Mission $mission): RedirectResponse
    {
        $this->abortUnlessActiveEdition($mission);

        $bookedCount = $mission->missionSlots()->withCount('volunteerAssignments')->get()->sum('volunteer_assignments_count');

        if ($bookedCount > 0) {
            return back()->with('error', "Impossible de supprimer « {$mission->name} » : {$bookedCount} réservation(s) de bénévoles dessus. Ferme plutôt ses créneaux ou retire les bénévoles.");
        }

        AuditLog::record($request->user(), 'mission.deleted', $mission, ['name' => $mission->name]);
        $mission->delete();

        return redirect()->route('admin.missions.index')->with('status', "Mission « {$mission->name} » supprimée.");
    }

    /**
     * Display the missions import form.
     */
    public function importForm(): View
    {
        return view('admin.missions.import', [
            'maxRows' => self::MAX_IMPORT_ROWS,
            'timeSlotCount' => $this->timeSlotsFor(Edition::active())->count(),
        ]);
    }

    /**
     * Create one mission per line of an Excel or CSV file: Mission, Description, Publique (Oui/Non), Capacité.
     */
    public function import(ImportMissionsRequest $request, SpreadsheetReader $spreadsheetReader): RedirectResponse
    {
        $rows = $spreadsheetReader->rows($request->file('file'));

        if (count($rows) > self::MAX_IMPORT_ROWS) {
            return back()->with('error', 'Le fichier dépasse '.self::MAX_IMPORT_ROWS.' lignes : découpe-le en plusieurs imports.');
        }

        $edition = Edition::active();
        $existingNames = Mission::where('edition_id', $edition->id)->pluck('name')->map(fn (string $name) => mb_strtolower($name))->all();
        $created = [];
        $skipped = [];

        foreach ($rows as $line => $cells) {
            [$name, $description, $publicCell, $capacityCell] = array_pad($cells, 4, '');

            if ($line === array_key_first($rows) && ! ctype_digit($capacityCell)) {
                continue;
            }

            $isPublic = $this->parsePublic($publicCell);

            $reason = match (true) {
                $name === '' => 'Nom de mission manquant',
                mb_strlen($name) > 255 => 'Nom de mission trop long (255 caractères maximum)',
                in_array(mb_strtolower($name), $existingNames, true) => 'Une mission porte déjà ce nom',
                $isPublic === null => "Colonne « Publique » invalide (« {$publicCell} ») : Oui ou Non attendu",
                ! ctype_digit($capacityCell) || (int) $capacityCell > 500 => 'Capacité manquante ou invalide (nombre de 0 à 500)',
                default => null,
            };

            if ($reason) {
                $skipped[] = ['line' => $line, 'value' => $name ?: '(vide)', 'reason' => $reason];

                continue;
            }

            $existingNames[] = mb_strtolower($name);
            $created[] = $this->createMission([
                'name' => $name,
                'description' => $description !== '' ? mb_substr($description, 0, 2000) : null,
                'is_public' => $isPublic,
                'default_capacity' => (int) $capacityCell,
            ], $request->user(), $edition)->name;
        }

        if ($created === [] && $skipped === []) {
            return back()->with('error', 'Aucune mission trouvée dans le fichier.');
        }

        return redirect()->route('admin.missions.index')
            ->with('status', count($created).' mission(s) importée(s).')
            ->with('importSkipped', $skipped);
    }

    /**
     * Create a mission and open it on every time slot of the edition with its default capacity.
     *
     * @param  array{name: string, description: ?string, is_public: bool, default_capacity: int}  $attributes
     */
    private function createMission(array $attributes, User $admin, Edition $edition): Mission
    {
        $mission = DB::transaction(function () use ($attributes, $edition) {
            $mission = Mission::create([...$attributes, 'edition_id' => $edition->id]);

            if ($mission->default_capacity > 0) {
                $this->timeSlotsFor($edition)->each(fn (TimeSlot $timeSlot) => MissionSlot::create([
                    'mission_id' => $mission->id,
                    'time_slot_id' => $timeSlot->id,
                    'capacity' => $mission->default_capacity,
                ]));
            }

            return $mission;
        });

        AuditLog::record($admin, 'mission.created', $mission, ['name' => $mission->name]);

        return $mission;
    }

    /**
     * « Oui » / « Non » (and common variants) to a boolean; an empty cell means public.
     */
    private function parsePublic(string $value): ?bool
    {
        return match (mb_strtolower(trim($value))) {
            '', 'oui', 'o', 'yes', 'y', '1', 'vrai', 'true', 'publique', 'public' => true,
            'non', 'n', 'no', '0', 'faux', 'false', 'restreinte', 'restreint' => false,
            default => null,
        };
    }

    /**
     * @return Collection<int, TimeSlot>
     */
    private function timeSlotsFor(Edition $edition): Collection
    {
        return TimeSlot::whereHas('eventDay', fn ($query) => $query->where('edition_id', $edition->id))->get();
    }

    /**
     * @return Collection<int, EventDay>
     */
    private function daysFor(Edition $edition): Collection
    {
        return EventDay::where('edition_id', $edition->id)
            ->with(['timeSlots' => fn ($query) => $query->orderBy('position')])
            ->orderBy('date')
            ->get();
    }

    /**
     * Missions of past or other editions are not managed from here.
     */
    private function abortUnlessActiveEdition(Mission $mission): void
    {
        abort_unless($mission->edition_id === Edition::active()->id, 404);
    }
}
