<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $table="m_academic_year";
    protected $primaryKey = 'academic_id';
    protected $fillable=['name'];

     public function listData(){
        return AcademicYear::pluck('name','academic_id')->toArray();
    }
}
