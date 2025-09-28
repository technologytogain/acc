<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holidays extends Model
{
    use HasFactory;

    protected $table="holidays";
    protected $primaryKey = 'holiday_id';

    protected $fillable=['status','date'];

     public function listData(){
        return Holidays::orderBy('name','ASC')->pluck('name','holiday_id')->toArray();
    }
}
