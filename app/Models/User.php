<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'sso_id',
        'employee_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    public function roles(): BelongsToMany { return $this->belongsToMany(Role::class); }
    public function isLegacyAdmin(): bool {
        $ids = array_filter(array_map('trim',explode(',',(string) config('iams.admin_user_ids',''))));
        return in_array((string)$this->getKey(),$ids,true);
    }
    public function hasPermission(string $permission): bool {
        if ($this->isLegacyAdmin()) return true;
        return $this->roles()->get()->contains(fn ($r) => in_array($permission,$r->permissions ?? [],true));
    }
    public function borrowingsProcessed() { return $this->hasMany(Borrowing::class,'processed_by'); }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
