<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        "arrived_at",
        "left_at",
        "employee_id",
    ];

    protected $dates = [
        "arrived_at",
        "left_at",
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
