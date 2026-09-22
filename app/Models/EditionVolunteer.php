<?php

namespace App\Models;

use Database\Factories\EditionVolunteerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Concerns\AsPivot;

#[Fillable(['user_id', 'edition_id', 'is_validated', 'validated_at', 'badge_uid'])]
class EditionVolunteer extends Model
{
    /** @use HasFactory<EditionVolunteerFactory> */
    use AsPivot, HasFactory;

    /**
     * The table associated with the model.
     *
     * AsPivot otherwise defaults to a singular table name convention.
     *
     * @var string
     */
    protected $table = 'edition_volunteers';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'validated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Edition>
     */
    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }
}
