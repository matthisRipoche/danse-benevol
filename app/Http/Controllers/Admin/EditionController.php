<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreEditionRequest;
use App\Http\Requests\Admin\UpdateEditionRequest;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\InvitationCode;
use App\Support\EditionScheduleCopier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EditionController extends Controller
{
    /**
     * List every edition, most recent first, with its key figures.
     */
    public function index(): View
    {
        return view('admin.editions.index', [
            'editions' => Edition::withCount(['volunteers', 'missions', 'eventDays'])->orderByDesc('start_date')->get(),
        ]);
    }

    /**
     * Display the form to create an edition.
     */
    public function create(): View
    {
        return view('admin.editions.create', [
            'sourceEditions' => Edition::has('missions')->orderByDesc('start_date')->get(),
        ]);
    }

    /**
     * Create a draft edition, optionally with the schedule and missions of a previous one.
     */
    public function store(StoreEditionRequest $request, EditionScheduleCopier $copier): RedirectResponse
    {
        $edition = Edition::create([
            ...$request->safe()->except('copy_from_edition_id'),
            'slug' => $this->uniqueSlug($request->validated('name')),
            'status' => 'draft',
        ]);

        $status = "Édition « {$edition->name} » créée en brouillon.";
        $source = null;
        $copied = null;

        if ($request->filled('copy_from_edition_id')) {
            $source = Edition::findOrFail($request->validated('copy_from_edition_id'));
            $copied = $copier->copy($source, $edition);
            $status .= " Copié depuis « {$source->name} » : {$copied['days']} jour(s), {$copied['timeSlots']} créneau(x), {$copied['missions']} mission(s).";
        }

        AuditLog::record($request->user(), 'edition.created', $edition, array_filter([
            'name' => $edition->name,
            'copied_from' => $source?->name,
            'copied' => $copied,
        ]));

        return redirect()->route('admin.editions.index')->with('status', $status);
    }

    /**
     * Display the form to edit an edition.
     */
    public function edit(Edition $edition): View
    {
        return view('admin.editions.edit', ['edition' => $edition]);
    }

    /**
     * Update an edition's name, dates and planning quotas.
     */
    public function update(UpdateEditionRequest $request, Edition $edition): RedirectResponse
    {
        $edition->update($request->validated());

        AuditLog::record($request->user(), 'edition.updated', $edition, ['name' => $edition->name]);

        return redirect()->route('admin.editions.index')->with('status', "Édition « {$edition->name} » mise à jour.");
    }

    /**
     * Make the edition the one the whole platform works on: the previous active edition is
     * archived and its pending invitation codes revoked, so they cannot enrol anyone in it.
     */
    public function activate(Request $request, Edition $edition): RedirectResponse
    {
        if ($edition->status === 'active') {
            return back()->with('error', "« {$edition->name} » est déjà l'édition active.");
        }

        [$archivedNames, $revokedCodeCount] = DB::transaction(function () use ($edition) {
            $previousEditions = Edition::where('status', 'active')->lockForUpdate()->get();

            Edition::whereKey($previousEditions->modelKeys())->update(['status' => 'archived']);
            $edition->update(['status' => 'active']);

            $revokedCodeCount = InvitationCode::where('edition_id', '!=', $edition->id)
                ->where('status', 'pending')
                ->update(['status' => 'revoked']);

            return [$previousEditions->pluck('name')->all(), $revokedCodeCount];
        });

        AuditLog::record($request->user(), 'edition.activated', $edition, [
            'name' => $edition->name,
            'archived' => $archivedNames,
            'revoked_invitation_codes' => $revokedCodeCount,
        ]);

        $status = "« {$edition->name} » est maintenant l'édition active.";

        if ($archivedNames !== []) {
            $status .= ' Archivée : « '.implode(' », « ', $archivedNames).' ».';
        }

        if ($revokedCodeCount > 0) {
            $status .= " {$revokedCodeCount} code(s) d'invitation en attente révoqué(s).";
        }

        return redirect()->route('admin.editions.index')->with('status', $status);
    }

    /**
     * A slug based on the name, suffixed when another edition already uses it.
     */
    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $candidate = $slug;
        $suffix = 2;

        while (Edition::where('slug', $candidate)->exists()) {
            $candidate = "{$slug}-{$suffix}";
            $suffix++;
        }

        return $candidate;
    }
}
