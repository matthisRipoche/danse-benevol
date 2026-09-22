<?php

namespace App\Models;

use Database\Factories\VolunteerAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'mission_slot_id', 'time_slot_id', 'status', 'assigned_by_id'])]
class VolunteerAssignment extends Model
{
    /** @use HasFactory<VolunteerAssignmentFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MissionSlot>
     */
    public function missionSlot(): BelongsTo
    {
        return $this->belongsTo(MissionSlot::class);
    }

    /**
     * @return BelongsTo<TimeSlot>
     */
    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }

    /**
     * @return BelongsTo<User>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id');
    }
}
