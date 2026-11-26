<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use App\Models\Concerns\LogsActivity;
use App\Support\MediaStorage;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Crypt;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, LogsActivity, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    public array $auditExclude = [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'last_login_at',
        'email_verified_at',
        'credentials_pending_at',
        'issued_password',
        'issued_password_at',
    ];

    public function getAuditLabel(): string
    {
        return 'ผู้ใช้ '.$this->name.' ('.($this->email ?: $this->phone).')';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'phone',
        'school_name',
        'school_province',
        'address_line1',
        'subdistrict',
        'district',
        'province',
        'postal_code',
        'avatar',
        'status',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'credentials_pending_at',
        'issued_password',
        'issued_password_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'credentials_pending_at' => 'datetime',
            'issued_password_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return MediaStorage::url($this->avatar, $this->updated_at?->getTimestamp());
    }

    protected function phone(): Attribute
    {
        return Attribute::set(fn (?string $value) => Phone::normalize($value));
    }

    protected static function booted(): void
    {
        static::updating(function (User $user) {
            // บัญชีที่สร้างตอนสั่งซื้อแบบ guest จะได้รหัสผ่านทางอีเมลหลังชำระเงิน ถ้ารหัสผ่านถูกตั้งทางอื่นก่อน ห้ามทับ
            if ($user->isDirty('password') && ! $user->isDirty('credentials_pending_at')) {
                $user->credentials_pending_at = null;
            }

            // เปลี่ยนรหัสผ่านโดยไม่ผ่าน setVisiblePassword() สำเนาเดิมจะไม่ตรง ต้องล้างทิ้ง
            if ($user->isDirty('password') && ! $user->isDirty('issued_password')) {
                $user->issued_password = null;
                $user->issued_password_at = null;
            }
        });
    }

    /**
     * ตั้งรหัสผ่านนักเรียนพร้อมเก็บสำเนาเข้ารหัส (APP_KEY) ให้แอดมินเปิดดูได้ ยังไม่บันทึก
     */
    public function setVisiblePassword(string $password): static
    {
        return $this->forceFill([
            'password' => $password,
            'issued_password' => Crypt::encryptString($password),
            'issued_password_at' => now(),
        ]);
    }

    /**
     * เก็บสำเนาของรหัสผ่านปัจจุบัน (ตรวจแล้วว่าถูกต้อง เช่น ตอนเข้าสู่ระบบ) โดยไม่เปลี่ยนรหัสผ่าน
     */
    public function rememberPasswordCopy(string $password): void
    {
        if ($this->issuedPassword() === $password) {
            return;
        }

        $this->forceFill([
            'issued_password' => Crypt::encryptString($password),
            'issued_password_at' => now(),
        ])->saveQuietly();
    }

    /**
     * null เมื่อยังไม่มีสำเนา (บัญชีเดิมที่ยังไม่เข้าสู่ระบบหลังเปิดระบบนี้) หรือถอดรหัสไม่ได้เพราะ APP_KEY เปลี่ยน
     */
    public function issuedPassword(): ?string
    {
        if (blank($this->issued_password)) {
            return null;
        }

        try {
            return Crypt::decryptString($this->issued_password);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Roles allowed to use this admin backend.
     *
     * @var list<string>
     */
    public const BACKEND_ROLES = ['admin', 'staff'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function scopeStudents($query)
    {
        return $query->whereHas('roles', fn ($roles) => $roles->where('name', 'student'));
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function canAccessBackend(): bool
    {
        return $this->hasAnyRole(self::BACKEND_ROLES);
    }

    public function isStudentOnly(): bool
    {
        $roleNames = $this->roles()->pluck('name');

        return $roleNames->isNotEmpty()
            && $roleNames->every(fn (string $name) => $name === 'student');
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function userSessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function videoProgress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    public function learningProgress(): HasMany
    {
        return $this->hasMany(LearningProgress::class);
    }

    public function assessmentAttempts(): HasMany
    {
        return $this->hasMany(AssessmentAttempt::class);
    }

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function activeCart(): HasOne
    {
        return $this->hasOne(Cart::class)->where('status', 'active');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function trialAccess(): HasMany
    {
        return $this->hasMany(TrialAccess::class);
    }

    public function createdContents(): HasMany
    {
        return $this->hasMany(Content::class, 'created_by');
    }
}
