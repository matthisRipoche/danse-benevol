<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreInvitationCodeRequest;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\InvitationCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvitationCodeController extends Controller
{
    /**
     * List the invitation codes for the active edition.
     */
    public function index(): View
    {
        $codes = Edition::active()
            ->invitationCodes()
            ->latest()
            ->get();

        return view('admin.invitation-codes.index', [
            'codes' => $codes,
        ]);
    }

    /**
     * Display the invitation code creation form.
     */
    public function create(): View
    {
        return view('admin.invitation-codes.create');
    }

    /**
     * Create a new invitation code for the active edition.
     */
    public function store(StoreInvitationCodeRequest $request): RedirectResponse
    {
        $code = InvitationCode::create([
            'edition_id' => Edition::active()->id,
            'code' => InvitationCode::generateUniqueCode(),
            'email' => $request->validated('email'),
            'status' => 'pending',
            'expires_at' => now()->addMonths(2),
            'created_by_id' => $request->user()->id,
        ]);

        AuditLog::record($request->user(), 'invitation_code.created', $code, [
            'email' => $code->email,
        ]);

        return redirect()->route('admin.invitation-codes.index')
            ->with('status', "Code d'invitation créé pour {$code->email}.");
    }

    /**
     * Revoke a pending invitation code.
     */
    public function revoke(Request $request, InvitationCode $invitationCode): RedirectResponse
    {
        if ($invitationCode->status !== 'pending') {
            return redirect()->route('admin.invitation-codes.index')
                ->with('error', 'Seul un code en attente peut être révoqué.');
        }

        $invitationCode->update(['status' => 'revoked']);

        AuditLog::record($request->user(), 'invitation_code.revoked', $invitationCode, [
            'email' => $invitationCode->email,
        ]);

        return redirect()->route('admin.invitation-codes.index')
            ->with('status', "Code d'invitation révoqué.");
    }
}
