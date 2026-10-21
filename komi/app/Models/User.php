<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Scopes\HideBlockedUsersScope;

#[Fillable([
    'name',
    'username',
    'email',
    'email_verified_at',
    'password',
    'birth_date',
    'bio',
    'avatar',
    'banner',
    'theme_color',
    'gender',
    'status',
    'is_verified',
    'is_premium',
    'is_global_admin',
    'warnings_count',
    'is_shadowbanned',
    'banned_until',
    'provider',
    'provider_id',
    'coins',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope(new HideBlockedUsersScope);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birth_date' => 'date',
            'last_checkin_at' => 'date',
            'is_verified' => 'boolean',
            'is_premium' => 'boolean',
            'is_global_admin' => 'boolean',
            'is_shadowbanned' => 'boolean',
            'banned_until' => 'datetime',
        ];
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function communities(): BelongsToMany
    {
        return $this->belongsToMany(Community::class, 'community_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedCommunities(): HasMany
    {
        return $this->hasMany(Community::class, 'owner_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(Reaction::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'user_badges')
            ->withPivot('awarded_at');
    }

    public function appeals(): HasMany
    {
        return $this->hasMany(Appeal::class);
    }

    public function adminLogs(): HasMany
    {
        return $this->hasMany(AdminLog::class, 'admin_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'user_id');
    }

    // ─── Store ────────────────────────────────────────────────

    public function storeItems(): HasMany
    {
        return $this->hasMany(StoreItem::class, 'seller_id');
    }

    public function storePurchases(): HasMany
    {
        return $this->hasMany(StorePurchase::class, 'buyer_id');
    }

    // ─── Cosmetics ───────────────────────────────────────────

    public function activeFrame(): BelongsTo
    {
        return $this->belongsTo(StoreItem::class, 'active_frame_id');
    }

    public function activeChatTheme(): BelongsTo
    {
        return $this->belongsTo(StoreItem::class, 'active_chat_theme_id');
    }

    public function twoFactor(): HasOne
    {
        return $this->hasOne(AdminTwoFactor::class);
    }

    // ─── Followers ────────────────────────────────────────────

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'following_id')
            ->withTimestamps();
    }

    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    // ─── Blocked Users ────────────────────────────────────────

    public function blockedUsers(): BelongsToMany
    {
        // Sin el scope: el usuario debe poder ver y desbloquear su propia lista.
        return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blocked_id')
            ->withoutGlobalScope(HideBlockedUsersScope::class)
            ->withTimestamps();
    }

    public function blockedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'blocker_id')
            ->withoutGlobalScope(HideBlockedUsersScope::class)
            ->withTimestamps();
    }

    public function isBlocking(User $user): bool
    {
        // Consulta directa al pivot: la relación blockedUsers pasa por el
        // modelo User y el scope global ocultaría al bloqueado.
        return UserBlock::query()
            ->where('blocker_id', $this->id)
            ->where('blocked_id', $user->id)
            ->exists();
    }

    public function isBlockedBy(User $user): bool
    {
        return UserBlock::query()
            ->where('blocker_id', $user->id)
            ->where('blocked_id', $this->id)
            ->exists();
    }

    public function hasBlockConflictWith(User $user): bool
    {
        return $this->isBlocking($user) || $user->isBlocking($this);
    }

    // ─── Utility Methods ──────────────────────────────────────

    public function isShadowbanned(): bool
    {
        return $this->is_shadowbanned;
    }

    public function isTempBanned(): bool
    {
        return $this->banned_until !== null && $this->banned_until->isFuture();
    }
}
