<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControlInfo extends Model{

    use HasFactory;

    protected $table="device_access_control_info";
    protected $primaryKey = 'access_info_id';

    public function scopeFilter($query,$request){
        return $query->where('device', $request['device'])->where('course',$request['course'])->where('department',$request['department'])->where('current_year',$request['current_year']);
    }
    
    public function scopeStatus($query,$status){
        return $query->where('status',$status);
    }

    public function device(){
        return $this->hasOne('App\Device','device','device_id');
    }
}
