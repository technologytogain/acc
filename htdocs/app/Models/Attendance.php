<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Attendance extends Model
{
    use HasFactory;

    protected $table="attendance";
    protected $primaryKey = 'attendance_id';

    public function scopeFilter($query,$request){

        if($request['course'] && !is_null($request['course']) )
            $query->where('course',$request['course']);
        if($request['department'] && !is_null($request['department']) )
            $query->where('department',$request['department']);
        if($request['academic_year'] && !is_null($request['academic_year']) )
            $query->where('academic_year',$request['academic_year']);
        /*if($request['year'] && !is_null($request['year']) )
            $query->where('year',$request['year']);*/
        
        return $query;
    }

    
    public function scopeStatus($query,$status){
        return $query->where('status',$status);
    }
}
