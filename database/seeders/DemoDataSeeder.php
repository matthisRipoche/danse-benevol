<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Edition;
use App\Models\EditionVolunteer;
use App\Models\EventDay;
use App\Models\InvitationCode;
use App\Models\Mission;
use App\Models\MissionSlot;
use App\Models\TimeSlot;
use App\Models\User;
use App\Models\VolunteerAssignment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Missions ouvertes à la réservation publique (cahier des charges §3).
     *
     * @var array<int, string>
     */
    protected const PUBLIC_MISSIONS = [
        'Accueil exposants',
        'Vestiaires',
        'Point Info',
        'Masterclass/Conférences',
        'Loges danseurs',
        'Logistique (Niveau 0)',
        'Logistique (Niveau -2)',
        'Scène principale',
        'Stand JayDance',
        'Village Danses du Monde',
    ];

    /**
     * Postes sensibles, hors-planning public, attribués manuellement par l'admin.
     *
     * @var array<int, string>
     */
    protected const RESTRICTED_MISSIONS = ['Billetterie', 'Caisse'];

    /**
     * @var array<string, string>
     */
    protected const EVENT_DAYS = [
        '2027-05-14' => 'Vendredi',
        '2027-05-15' => 'Samedi',
        '2027-05-16' => 'Dimanche',
    ];

    /**
     * @var array<int, array{0: string, 1: string}>
     */
    protected const TIME_SLOTS = [
        1 => ['08:30:00', '10:00:00'],
        2 => ['10:00:00', '12:00:00'],
        3 => ['12:00:00', '14:00:00'],
        4 => ['14:00:00', '16:00:00'],
        5 => ['16:00:00', '18:00:00'],
    ];

    protected const VOLUNTEER_COUNT = 130;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $edition = $this->seedEdition();
        $timeSlots = $this->seedEventDaysAndTimeSlots($edition);
        [$publicMissionSlots, $restrictedMissionSlots] = $this->seedMissions($edition, $timeSlots);
        $admins = $this->seedAdmins();
        $volunteers = $this->seedVolunteers($edition, $admins->first());

        $this->seedAssignments($edition, $volunteers, $timeSlots, $publicMissionSlots, $restrictedMissionSlots, $admins->last());
    }

    protected function seedEdition(): Edition
    {
        return Edition::factory()->create([
            'name' => 'Salon de la Danse 2027',
            'slug' => 'salon-de-la-danse-2027',
            'start_date' => '2027-05-14',
            'end_date' => '2027-05-16',
            'registration_opens_at' => now()->subMonths(2),
            'registration_closes_at' => now()->addMonth(),
            'is_registration_locked' => false,
            'status' => 'active',
        ]);
    }

    /**
     * @return Collection<int, TimeSlot>
     */
    protected function seedEventDaysAndTimeSlots(Edition $edition): Collection
    {
        return collect(self::EVENT_DAYS)
            ->flatMap(function (string $label, string $date) use ($edition) {
                $day = EventDay::factory()->for($edition)->create([
                    'date' => $date,
                    'label' => $label,
                ]);

                return collect(self::TIME_SLOTS)->map(fn (array $hours, int $position) => TimeSlot::factory()->for($day, 'eventDay')->create([
                    'starts_at' => $hours[0],
                    'ends_at' => $hours[1],
                    'position' => $position,
                ]));
            })
            ->values();
    }

    /**
     * @param  Collection<int, TimeSlot>  $timeSlots
     * @return array{0: Collection<int, MissionSlot>, 1: Collection<int, MissionSlot>}
     */
    protected function seedMissions(Edition $edition, Collection $timeSlots): array
    {
        $publicMissionSlots = collect(self::PUBLIC_MISSIONS)
            ->flatMap(function (string $name) use ($edition, $timeSlots) {
                $mission = Mission::factory()->for($edition)->create([
                    'name' => $name,
                    'is_public' => true,
                ]);

                return $timeSlots->map(fn (TimeSlot $timeSlot) => MissionSlot::factory()->for($mission)->for($timeSlot, 'timeSlot')->create([
                    'capacity' => fake()->numberBetween(3, 8),
                ]));
            })
            ->values();

        $restrictedMissionSlots = collect(self::RESTRICTED_MISSIONS)
            ->flatMap(function (string $name) use ($edition, $timeSlots) {
                $mission = Mission::factory()->for($edition)->create([
                    'name' => $name,
                    'is_public' => false,
                ]);

                return $timeSlots->map(fn (TimeSlot $timeSlot) => MissionSlot::factory()->for($mission)->for($timeSlot, 'timeSlot')->create([
                    'capacity' => fake()->numberBetween(1, 2),
                ]));
            })
            ->values();

        return [$publicMissionSlots, $restrictedMissionSlots];
    }

    /**
     * @return Collection<int, User>
     */
    protected function seedAdmins(): Collection
    {
        return collect([
            User::factory()->admin()->create([
                'first_name' => 'Camille',
                'last_name' => 'Roussel',
                'email' => 'admin@salon-danse.fr',
            ]),
            User::factory()->admin()->create(),
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    protected function seedVolunteers(Edition $edition, User $admin): Collection
    {
        return collect(range(1, self::VOLUNTEER_COUNT))->map(function () use ($edition, $admin) {
            $isMinor = fake()->boolean(8);

            $volunteer = User::factory()->create([
                'is_minor' => $isMinor,
                'minor_validated_at' => $isMinor && fake()->boolean(70) ? now() : null,
            ]);

            InvitationCode::factory()->used()->for($edition)->create([
                'used_by_user_id' => $volunteer->id,
                'used_at' => $volunteer->created_at,
                'created_by_id' => $admin->id,
            ]);

            return $volunteer;
        });
    }

    /**
     * Assigns each volunteer 1 to `max_slots_per_volunteer` distinct time slots, respecting
     * the mission slot capacity and the "no more than `max_consecutive_slots` in a row" rule,
     * then locks a handful of restricted mission slots via forced admin assignments.
     *
     * @param  Collection<int, User>  $volunteers
     * @param  Collection<int, TimeSlot>  $timeSlots
     * @param  Collection<int, MissionSlot>  $publicMissionSlots
     * @param  Collection<int, MissionSlot>  $restrictedMissionSlots
     */
    protected function seedAssignments(
        Edition $edition,
        Collection $volunteers,
        Collection $timeSlots,
        Collection $publicMissionSlots,
        Collection $restrictedMissionSlots,
        User $admin,
    ): void {
        $remainingCapacity = $publicMissionSlots->pluck('capacity', 'id')->all();
        $restrictedRemainingCapacity = $restrictedMissionSlots->pluck('capacity', 'id')->all();
        $missionSlotsByTimeSlot = $publicMissionSlots->groupBy('time_slot_id');
        $restrictedMissionSlotsByTimeSlot = $restrictedMissionSlots->groupBy('time_slot_id');
        $timeSlotMeta = $timeSlots->mapWithKeys(fn (TimeSlot $slot) => [
            $slot->id => ['day' => $slot->event_day_id, 'position' => $slot->position],
        ]);

        $volunteers->each(function (User $volunteer, int $index) use (
            $edition,
            $timeSlots,
            $timeSlotMeta,
            $missionSlotsByTimeSlot,
            $restrictedMissionSlotsByTimeSlot,
            &$remainingCapacity,
            &$restrictedRemainingCapacity,
            $admin,
        ) {
            $chosen = $this->pickAssignmentsForVolunteer(
                $edition, $timeSlots, $timeSlotMeta, $missionSlotsByTimeSlot, $remainingCapacity
            );

            $isValidated = count($chosen) > 0 && fake()->boolean(70);
            $status = $isValidated ? 'validated' : 'draft';

            foreach ($chosen as $pick) {
                VolunteerAssignment::factory()->create([
                    'user_id' => $volunteer->id,
                    'mission_slot_id' => $pick['mission_slot_id'],
                    'time_slot_id' => $pick['time_slot_id'],
                    'status' => $status,
                    'assigned_by_id' => null,
                ]);
            }

            EditionVolunteer::factory()->for($volunteer)->for($edition)->create([
                'is_validated' => $isValidated,
                'validated_at' => $isValidated ? now() : null,
                'badge_uid' => $isValidated ? Str::uuid()->toString() : null,
            ]);

            if ($isValidated) {
                $volunteer->update(['profile_locked_at' => now()]);
            }

            if ($index < 6) {
                $this->forceRestrictedAssignment(
                    $volunteer, $admin, $timeSlots, $chosen, $restrictedMissionSlotsByTimeSlot, $restrictedRemainingCapacity
                );
            }
        });
    }

    /**
     * @param  Collection<int, TimeSlot>  $timeSlots
     * @param  Collection<int, mixed>  $timeSlotMeta
     * @param  Collection<int, Collection<int, MissionSlot>>  $missionSlotsByTimeSlot
     * @param  array<int, int>  $remainingCapacity
     * @return array<int, array{time_slot_id: int, mission_slot_id: int}>
     */
    protected function pickAssignmentsForVolunteer(
        Edition $edition,
        Collection $timeSlots,
        Collection $timeSlotMeta,
        Collection $missionSlotsByTimeSlot,
        array &$remainingCapacity,
    ): array {
        $targetSlots = fake()->numberBetween($edition->min_slots_per_volunteer, $edition->max_slots_per_volunteer);
        $chosen = [];
        $chosenPositionsByDay = [];

        foreach ($timeSlots->shuffle() as $timeSlot) {
            if (count($chosen) >= $targetSlots) {
                break;
            }

            $dayId = $timeSlotMeta[$timeSlot->id]['day'];
            $position = $timeSlotMeta[$timeSlot->id]['position'];
            $dayPositions = $chosenPositionsByDay[$dayId] ?? [];

            if ($this->wouldExceedMaxConsecutive([...$dayPositions, $position], $edition->max_consecutive_slots)) {
                continue;
            }

            $availableMissionSlots = ($missionSlotsByTimeSlot[$timeSlot->id] ?? collect())
                ->filter(fn (MissionSlot $slot) => $remainingCapacity[$slot->id] > 0);

            if ($availableMissionSlots->isEmpty()) {
                continue;
            }

            $missionSlot = $availableMissionSlots->random();
            $remainingCapacity[$missionSlot->id]--;

            $chosen[] = ['time_slot_id' => $timeSlot->id, 'mission_slot_id' => $missionSlot->id];
            $chosenPositionsByDay[$dayId] = [...$dayPositions, $position];
        }

        return $chosen;
    }

    /**
     * @param  Collection<int, TimeSlot>  $timeSlots
     * @param  array<int, array{time_slot_id: int, mission_slot_id: int}>  $existingAssignments
     * @param  Collection<int, Collection<int, MissionSlot>>  $restrictedMissionSlotsByTimeSlot
     * @param  array<int, int>  $restrictedRemainingCapacity
     */
    protected function forceRestrictedAssignment(
        User $volunteer,
        User $admin,
        Collection $timeSlots,
        array $existingAssignments,
        Collection $restrictedMissionSlotsByTimeSlot,
        array &$restrictedRemainingCapacity,
    ): void {
        $usedTimeSlotIds = collect($existingAssignments)->pluck('time_slot_id');

        $unusedSlot = $timeSlots->first(fn (TimeSlot $slot) => ! $usedTimeSlotIds->contains($slot->id));

        if (! $unusedSlot) {
            return;
        }

        $availableRestricted = ($restrictedMissionSlotsByTimeSlot[$unusedSlot->id] ?? collect())
            ->filter(fn (MissionSlot $slot) => $restrictedRemainingCapacity[$slot->id] > 0);

        if ($availableRestricted->isEmpty()) {
            return;
        }

        $restrictedSlot = $availableRestricted->random();
        $restrictedRemainingCapacity[$restrictedSlot->id]--;

        VolunteerAssignment::factory()->create([
            'user_id' => $volunteer->id,
            'mission_slot_id' => $restrictedSlot->id,
            'time_slot_id' => $unusedSlot->id,
            'status' => 'validated',
            'assigned_by_id' => $admin->id,
        ]);

        AuditLog::factory()->create([
            'admin_id' => $admin->id,
            'action' => 'planning.override',
            'subject_type' => 'VolunteerAssignment',
            'subject_id' => $volunteer->id,
            'changes' => ['mission' => $restrictedSlot->mission->name, 'time_slot_id' => $unusedSlot->id],
        ]);
    }

    /**
     * @param  array<int, int>  $positions
     */
    protected function wouldExceedMaxConsecutive(array $positions, int $maxConsecutive): bool
    {
        sort($positions);
        $run = 1;

        for ($i = 1; $i < count($positions); $i++) {
            $run = $positions[$i] === $positions[$i - 1] + 1 ? $run + 1 : 1;

            if ($run > $maxConsecutive) {
                return true;
            }
        }

        return false;
    }
}
