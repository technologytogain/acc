<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $table="m_course";
    protected $primaryKey = 'course_id';

     public function listData(){
        return Course::pluck('name','course_id')->toArray();
    }
}
