<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bloodgroup extends Model
{
    use HasFactory;

    protected $table="m_bloodgroup";
    protected $primaryKey = 'bloodgroup_id';

    public $fillable=['name','status','updated_at'];

    public function listData(){
        return Bloodgroup::pluck('name','bloodgroup_id')->toArray();
    }


}
