<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMaster extends Model
{
    use HasFactory;

    protected $table="role_group_master";
    protected $primaryKey = 'rogroup_master_id';
    protected $fillable=['rogroup_master_name'];

     public static function listData(){
        return GroupMaster::where('status',1)->orderBy('rogroup_master_id','ASC')->pluck('rogroup_master_name','rogroup_master_id')->toArray();
    }
}
