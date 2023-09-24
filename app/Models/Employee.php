<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static find(mixed $id)
 * @method static create(array $employeeData)
 */
class Employee extends Model{
    use HasFactory;

    // fillable columns for the table
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'job_position',
        'avatar',
        'date_hired'
    ];

}
