<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $table="m_department";
    protected $primaryKey = 'department_id';

     public function listData($course=""){
         if($course)
            return Department::where('course',$course)->pluck('name','department_id')->toArray();
         else
            return Department::pluck('name','department_id')->toArray();
    }
}
