<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRegistrationWindowRequest;
use App\Models\AuditLog;
use App\Models\Edition;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class RegistrationWindowController extends Controller
{
    /**
     * Save the opening and closing dates of the active edition's registrations,
     * entered in the display timezone (Paris) and stored in UTC.
     */
    public function update(UpdateRegistrationWindowRequest $request): RedirectResponse
    {
        $edition = Edition::active();
        $toUtc = fn (?string $value) => $value ? Carbon::parse($value, config('app.display_timezone'))->utc() : null;

        $edition->update([
            'registration_opens_at' => $toUtc($request->validated('registration_opens_at')),
            'registration_closes_at' => $toUtc($request->validated('registration_closes_at')),
        ]);

        AuditLog::record($request->user(), 'edition.registration_window_updated', $edition, [
            'opens_at' => $edition->registration_opens_at?->toIso8601String(),
            'closes_at' => $edition->registration_closes_at?->toIso8601String(),
        ]);

        return redirect()->route('admin.dashboard')->with('status', "Dates d'inscription enregistrées.");
    }

    /**
     * Suspend registrations at once, whatever the dates, or lift the suspension.
     */
    public function toggleLock(Request $request): RedirectResponse
    {
        $edition = Edition::active();
        $edition->update(['is_registration_locked' => ! $edition->is_registration_locked]);

        AuditLog::record($request->user(), $edition->is_registration_locked ? 'edition.registration_locked' : 'edition.registration_unlocked', $edition);

        return redirect()->route('admin.dashboard')->with('status', $edition->is_registration_locked
            ? 'Inscriptions suspendues : les plannings des bénévoles sont en lecture seule.'
            : 'Inscriptions rouvertes, selon les dates prévues.');
    }
}
