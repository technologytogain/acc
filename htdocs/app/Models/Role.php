<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $table="role";
    protected $primaryKey = 'ro_id';
    protected $fillable=['ro_name'];

     public static function listData(){
        return Role::whereNotIn('ro_id',[1,3])->pluck('ro_name','ro_id')->toArray();
    }
}
