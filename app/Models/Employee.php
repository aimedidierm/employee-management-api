<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, HasFactory, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'code',
        'national_id',
        'phone',
        'dob',
        'status',
        'position',
        'password',
    ];

    protected $casts = [
        "reset_code_expires_in" => "datetime",
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        "reset_code",
        "reset_code_expires_in"
    ];

    public function scopeManager($query)
    {
        $query->where("position", UserType::MANAGER->value);
    }

    public function scopeNotManager($query)
    {
        $query->where("position", "<>", UserType::MANAGER->value);
    }

    public function scopeActive($query)
    {
        $query->where("status", UserStatus::ACTIVE->value);
    }

    public function scopeSuspended($query)
    {
        $query->where("status", "<>", UserStatus::ACTIVE->value);
    }

    public function scopeCode($query, $code)
    {
        $query->whereCode($code);
    }

    public function isManager()
    {
        return $this->position == UserType::MANAGER->value;
    }

    public function isActive()
    {
        return $this->status == UserStatus::ACTIVE->value;
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function todayAttendance()
    {
        return $this->hasOne(Attendance::class)->whereDate("arrived_at", now());
    }
}
