<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Year extends Model
{
    use HasFactory;

    protected $table="m_year";
    protected $primaryKey = 'year_id';

    public $fillable=['name','status','updated_at'];

    public function listData(){
        return Year::pluck('name','year_id')->toArray();
    }
}
