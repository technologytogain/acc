<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model{
    use HasFactory;

    protected $table="students";
    protected $primaryKey="stud_id";
    protected $fillable = ['first_name','last_name','father_name','dob','blood_group','register_no','department','contact_no','academic_year','status','uniqueid','photo','name','device_uniqueid','department','contact_no','academic_year','religion','community','email','state','address','course','current_year','gender','device','parent_contactno','parent_contactno2','mother_name','user'];

    public function scopeFilter($query,$request){
        //return $query->where('course',$request['course'])->where('department',$request['department'])->where('current_year',$request['year']);
        return $query->where('course',$request['course'])->where('department',$request['department'])->where('academic_year',$request['academic_year']);
    }
    public function scopeYearfilter($query,$request){
        return $query->where('course',$request['course'])->where('department',$request['department'])->where('current_year',$request['current_year']);
    }
    public function scopeStatus($query,$status){
        return $query->where('status',$status);
    }
}
