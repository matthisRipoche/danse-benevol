<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ImportInvitationCodesRequest;
use App\Http\Requests\Admin\StoreInvitationCodeRequest;
use App\Mail\InvitationCodeMail;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\InvitationCode;
use App\Models\User;
use App\Support\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class InvitationCodeController extends Controller
{
    /**
     * Maximum number of candidate lines accepted in a single import.
     */
    private const int MAX_IMPORT_ROWS = 500;

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
        $code = $this->issueCode($request->validated('email'), $request->user(), Edition::active());

        return redirect()->route('admin.invitation-codes.index')
            ->with('status', "Code d'invitation créé et envoyé à {$code->email}.");
    }

    /**
     * Display the candidates import form.
     */
    public function importForm(): View
    {
        return view('admin.invitation-codes.import', [
            'maxRows' => self::MAX_IMPORT_ROWS,
        ]);
    }

    /**
     * Create and send an invitation code to every valid candidate e-mail of an Excel or CSV file.
     */
    public function import(ImportInvitationCodesRequest $request, SpreadsheetReader $spreadsheetReader): RedirectResponse
    {
        $cells = array_filter(array_map(fn (array $row) => $row[0] ?? '', $spreadsheetReader->rows($request->file('file'))));

        if (count($cells) > self::MAX_IMPORT_ROWS) {
            return back()->with('error', 'Le fichier dépasse '.self::MAX_IMPORT_ROWS.' lignes : découpe-le en plusieurs imports.');
        }

        $edition = Edition::active();
        $created = [];
        $skipped = [];
        $seenEmails = [];

        foreach ($cells as $line => $value) {
            $email = trim($value);
            $normalizedEmail = mb_strtolower($email);

            if ($line === array_key_first($cells) && ! str_contains($email, '@')) {
                continue;
            }

            $reason = match (true) {
                Validator::make(['email' => $email], ['email' => ['required', 'string', 'email', 'max:255']])->fails() => 'Adresse e-mail invalide',
                in_array($normalizedEmail, $seenEmails, true) => 'En double dans le fichier',
                User::where('email', $email)->exists() => 'Un compte existe déjà avec cet e-mail',
                $edition->invitationCodes()->where('email', $email)->where('status', 'pending')->exists() => 'Un code est déjà en attente pour cet e-mail',
                default => null,
            };

            $seenEmails[] = $normalizedEmail;

            if ($reason) {
                $skipped[] = ['line' => $line, 'value' => $email, 'reason' => $reason];

                continue;
            }

            $created[] = $this->issueCode($email, $request->user(), $edition)->email;
        }

        if ($created === [] && $skipped === []) {
            return back()->with('error', 'Aucune adresse e-mail trouvée dans la première colonne du fichier.');
        }

        return redirect()->route('admin.invitation-codes.index')
            ->with('status', count($created).' code(s) créé(s) et envoyé(s).')
            ->with('importSkipped', $skipped);
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

    /**
     * Create a pending invitation code for the e-mail, log it and send it.
     */
    private function issueCode(string $email, User $admin, Edition $edition): InvitationCode
    {
        $code = InvitationCode::create([
            'edition_id' => $edition->id,
            'code' => InvitationCode::generateUniqueCode(),
            'email' => $email,
            'status' => 'pending',
            'expires_at' => now()->addMonths(2),
            'created_by_id' => $admin->id,
        ]);

        AuditLog::record($admin, 'invitation_code.created', $code, [
            'email' => $code->email,
        ]);

        Mail::to($code->email)->send(new InvitationCodeMail($code));

        return $code;
    }
}
