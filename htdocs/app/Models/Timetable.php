<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $table="timetable";
    protected $primaryKey = 'timetable_id';
    protected $fillable = [
        'timetable_id','department','course','year','subject','timeslot','theory_practical','weekday','status','created_at','updated_at','from_time','to_time','lunchbreak','combained_periods'
    ];

}
