<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionMaster extends Model
{
    use HasFactory;

    protected $table="role_action_master";
    protected $primaryKey = 'actmas_id';
    protected $fillable=['actmas_name'];

     public static function listData($groupid){
        return ActionMaster::where('actmas_group_id',$groupid)->where('status',1)->pluck('actmas_display_name','actmas_id')->toArray();
    }
}
