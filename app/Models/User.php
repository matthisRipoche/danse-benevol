<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['first_name', 'last_name', 'email', 'phone', 'password', 'photo_path', 'role', 'is_minor'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_minor' => 'boolean',
            'minor_validated_at' => 'datetime',
            'profile_locked_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The invitation codes created by this user (as an admin).
     *
     * @return HasMany<InvitationCode>
     */
    public function createdInvitationCodes(): HasMany
    {
        return $this->hasMany(InvitationCode::class, 'created_by_id');
    }

    /**
     * The invitation code this user consumed to register.
     *
     * @return HasOne<InvitationCode>
     */
    public function usedInvitationCode(): HasOne
    {
        return $this->hasOne(InvitationCode::class, 'used_by_user_id');
    }

    /**
     * The mission/time-slot reservations made by this user.
     *
     * @return HasMany<VolunteerAssignment>
     */
    public function volunteerAssignments(): HasMany
    {
        return $this->hasMany(VolunteerAssignment::class);
    }

    /**
     * The editions this user has participated in as a volunteer.
     *
     * @return BelongsToMany<Edition>
     */
    public function editions(): BelongsToMany
    {
        return $this->belongsToMany(Edition::class, 'edition_volunteers')
            ->using(EditionVolunteer::class)
            ->withPivot(['is_validated', 'validated_at', 'badge_uid'])
            ->withTimestamps();
    }

    /**
     * The admin actions performed by this user.
     *
     * @return HasMany<AuditLog>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }

    /**
     * The route name this user should land on once authenticated.
     */
    public function homeRouteName(): string
    {
        return $this->role === 'admin' ? 'admin.dashboard' : 'planning.index';
    }

    /**
     * Update the personal information and, when a new photo is given, replace the badge photo
     * on the private disk (the previous file is deleted once the new path is saved).
     *
     * @param  array<string, mixed>  $attributes
     * @return list<string> The names of the columns that actually changed.
     */
    public function updatePersonalInformation(array $attributes, ?UploadedFile $photo = null): array
    {
        $previousPhotoPath = $this->photo_path;

        $this->fill($attributes);

        if ($photo) {
            $this->photo_path = $photo->store('photos', 'local');
        }

        $changedFields = array_keys($this->getDirty());

        $this->save();

        if ($photo && $previousPhotoPath) {
            Storage::disk('local')->delete($previousPhotoPath);
        }

        return $changedFields;
    }
}
