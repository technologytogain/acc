<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subjects extends Model
{
    use HasFactory;

    protected $table="m_subjects";
    protected $primaryKey = 'subject_id';

     public function listData(){
        return Subjects::orderBy('name','ASC')->pluck('name','subject_id')->toArray();
    }
}
