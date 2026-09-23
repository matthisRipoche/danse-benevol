<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Options as CsvOptions;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Available exports, keyed by their URL slug.
     *
     * @var array<string, array{label: string, description: string}>
     */
    public const array EXPORTS = [
        'planning' => [
            'label' => 'Planning général',
            'description' => 'Tous les créneaux réservés, triés par jour et horaire, avec les coordonnées de chaque bénévole.',
        ],
        'missions' => [
            'label' => 'Listes par mission',
            'description' => 'Pour les responsables de poste : un onglet par mission en Excel, lignes regroupées par mission en CSV.',
        ],
        'contacts' => [
            'label' => 'Fiches contact',
            'description' => 'Un bénévole par ligne : coordonnées, statut mineur, planning validé et missions.',
        ],
    ];

    /**
     * Display the available exports.
     */
    public function index(): View
    {
        return view('admin.exports.index', [
            'edition' => Edition::active(),
            'exports' => self::EXPORTS,
        ]);
    }

    /**
     * Stream an export of the active edition as an Excel or CSV file.
     */
    public function download(Request $request, string $type, string $format): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::EXPORTS) && in_array($format, ['xlsx', 'csv'], true), 404);

        $edition = Edition::active();

        $sheets = match ($type) {
            'planning' => $this->planningSheets($edition),
            'missions' => $this->missionSheets($edition),
            'contacts' => $this->contactSheets($edition),
        };

        AuditLog::record($request->user(), 'export.downloaded', $edition, [
            'type' => $type,
            'format' => $format,
        ]);

        return response()->streamDownload(
            fn () => $format === 'xlsx' ? $this->writeXlsx($sheets) : $this->writeCsv($sheets),
            "{$edition->slug}-{$type}-".now()->format('Y-m-d').".{$format}",
            ['Content-Type' => $format === 'xlsx'
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8'],
        );
    }

    /**
     * @return array<string, array{headers: list<string>, rows: list<list<string|int>>}>
     */
    private function planningSheets(Edition $edition): array
    {
        $rows = $this->assignmentsFor($edition)->map(fn (VolunteerAssignment $assignment) => [
            $assignment->missionSlot->timeSlot->eventDay->label,
            $assignment->missionSlot->timeSlot->eventDay->date->format('d/m/Y'),
            substr($assignment->missionSlot->timeSlot->starts_at, 0, 5),
            substr($assignment->missionSlot->timeSlot->ends_at, 0, 5),
            $assignment->missionSlot->mission->name,
            $assignment->missionSlot->mission->is_public ? 'Non' : 'Oui',
            $assignment->user->last_name,
            $assignment->user->first_name,
            $assignment->user->email,
            $assignment->user->phone,
            $assignment->status === 'validated' ? 'Validé' : 'Brouillon',
        ]);

        return [
            'Planning' => [
                'headers' => ['Jour', 'Date', 'Début', 'Fin', 'Mission', 'Poste restreint', 'Nom', 'Prénom', 'E-mail', 'Téléphone', 'Statut'],
                'rows' => $rows->values()->all(),
            ],
        ];
    }

    /**
     * @return array<string, array{headers: list<string>, rows: list<list<string|int>>}>
     */
    private function missionSheets(Edition $edition): array
    {
        $headers = ['Mission', 'Jour', 'Date', 'Début', 'Fin', 'Nom', 'Prénom', 'Téléphone', 'E-mail', 'Statut'];

        $sheets = $this->assignmentsFor($edition)
            ->groupBy(fn (VolunteerAssignment $assignment) => $assignment->missionSlot->mission->name)
            ->sortKeys()
            ->map(fn (Collection $assignments) => [
                'headers' => $headers,
                'rows' => $assignments->map(fn (VolunteerAssignment $assignment) => [
                    $assignment->missionSlot->mission->name,
                    $assignment->missionSlot->timeSlot->eventDay->label,
                    $assignment->missionSlot->timeSlot->eventDay->date->format('d/m/Y'),
                    substr($assignment->missionSlot->timeSlot->starts_at, 0, 5),
                    substr($assignment->missionSlot->timeSlot->ends_at, 0, 5),
                    $assignment->user->last_name,
                    $assignment->user->first_name,
                    $assignment->user->phone,
                    $assignment->user->email,
                    $assignment->status === 'validated' ? 'Validé' : 'Brouillon',
                ])->values()->all(),
            ])
            ->all();

        return $sheets ?: ['Missions' => ['headers' => $headers, 'rows' => []]];
    }

    /**
     * @return array<string, array{headers: list<string>, rows: list<list<string|int>>}>
     */
    private function contactSheets(Edition $edition): array
    {
        $volunteers = $edition->volunteers()
            ->with(['volunteerAssignments' => fn ($query) => $query
                ->whereHas('missionSlot.timeSlot.eventDay', fn ($q) => $q->where('edition_id', $edition->id))
                ->with('missionSlot.mission')])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return [
            'Contacts' => [
                'headers' => ['Nom', 'Prénom', 'E-mail', 'Téléphone', 'Mineur', 'Planning', 'Créneaux', 'Missions'],
                'rows' => $volunteers->map(fn (User $volunteer) => [
                    $volunteer->last_name,
                    $volunteer->first_name,
                    $volunteer->email,
                    $volunteer->phone,
                    match (true) {
                        ! $volunteer->is_minor => 'Non',
                        (bool) $volunteer->minor_validated_at => 'Oui (validé)',
                        default => 'Oui (à valider)',
                    },
                    $volunteer->pivot->is_validated ? 'Validé' : 'En attente',
                    $volunteer->volunteerAssignments->count(),
                    $volunteer->volunteerAssignments->pluck('missionSlot.mission.name')->unique()->sort()->join(', '),
                ])->all(),
            ],
        ];
    }

    /**
     * The edition's assignments with their volunteer, mission and time slot, in chronological order.
     *
     * @return Collection<int, VolunteerAssignment>
     */
    private function assignmentsFor(Edition $edition): Collection
    {
        return VolunteerAssignment::whereHas('missionSlot.timeSlot.eventDay', fn ($q) => $q->where('edition_id', $edition->id))
            ->with('user', 'missionSlot.mission', 'missionSlot.timeSlot.eventDay')
            ->get()
            ->sortBy(fn (VolunteerAssignment $assignment) => $assignment->missionSlot->timeSlot->eventDay->date->format('Y-m-d')
                .$assignment->missionSlot->timeSlot->starts_at
                .$assignment->missionSlot->mission->name
                .$assignment->user->last_name)
            ->values();
    }

    /**
     * @param  array<string, array{headers: list<string>, rows: list<list<string|int>>}>  $sheets
     */
    private function writeXlsx(array $sheets): void
    {
        $writer = new XlsxWriter;
        $writer->openToFile('php://output');
        $usedNames = [];

        foreach (array_keys($sheets) as $index => $name) {
            $sheet = $index === 0 ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
            $sheetName = $this->sheetName($name, $usedNames);
            $usedNames[] = $sheetName;
            $sheet->setName($sheetName);

            $writer->addRow(Row::fromValuesWithStyle($sheets[$name]['headers'], new Style(fontBold: true)));

            foreach ($sheets[$name]['rows'] as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        }

        $writer->close();
    }

    /**
     * Write every sheet one after another under a single header, with the separator Excel expects in French.
     *
     * @param  array<string, array{headers: list<string>, rows: list<list<string|int>>}>  $sheets
     */
    private function writeCsv(array $sheets): void
    {
        $writer = new CsvWriter(new CsvOptions(FIELD_DELIMITER: ';'));
        $writer->openToFile('php://output');
        $writer->addRow(Row::fromValues(reset($sheets)['headers']));

        foreach ($sheets as $sheet) {
            foreach ($sheet['rows'] as $row) {
                $writer->addRow(Row::fromValues($row));
            }
        }

        $writer->close();
    }

    /**
     * An Excel-safe, unique sheet name: at most 31 characters, without the characters Excel forbids.
     *
     * @param  list<string>  $usedNames
     */
    private function sheetName(string $name, array $usedNames): string
    {
        $base = mb_substr(trim((string) preg_replace('/\s+/', ' ', str_replace(['\\', '/', '?', '*', ':', '[', ']'], ' ', $name))) ?: 'Feuille', 0, 31);
        $candidate = $base;

        for ($suffix = 2; in_array(mb_strtolower($candidate), array_map('mb_strtolower', $usedNames), true); $suffix++) {
            $candidate = mb_substr($base, 0, 31 - strlen(" ({$suffix})"))." ({$suffix})";
        }

        return $candidate;
    }
}
