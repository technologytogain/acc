<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;

    protected $table="m_class_room";
    protected $primaryKey = 'room_id';

     public function listData(){
        return ClassRoom::pluck('name','room_id')->toArray();
    }
}
