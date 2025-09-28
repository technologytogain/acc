<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControl extends Model
{
    use HasFactory;

    protected $table="device_access_control";
    protected $primaryKey = 'access_id';
    protected $fillable = ['student','department','device','user_id','status','course','current_year','device_student_id','created_by','updated_by','register_no','academic_year'];

    public function scopeFilter($query,$request){
        return $query->where('device', $request['device'])->where('course',$request['course'])->where('department',$request['department'])->where('academic_year',$request['academic_year']);
        //->where('current_year',$request['current_year']);
    }
    
    public function scopeStatus($query,$status){
        return $query->where('status',$status);
    }

    public function device(){
        return $this->hasOne('App\Device','device','device_id');
    }
}
