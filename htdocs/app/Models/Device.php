<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;

    protected $table="device";
    protected $primaryKey = 'device_id';
    protected $fillable = [
        'device_id', 'name', 'ip', 'protocol', 'model', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by', 'port', 'username', 'password','devIndex','devResponse','verification_status','type','device_student_id','device_status','room'
    ];

    public function listData(){
        return Device::where('status',1)->orderBy('name','ASC')->pluck('name','device_id')->toArray();
    }
    public function listDataName(){
        return Device::where('status',1)->orderBy('name','ASC')->pluck('name','name')->toArray();
    }

}
