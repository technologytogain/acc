<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    use HasFactory;

    protected $table="m_religion";
    protected $primaryKey = 'religion_id';

    public $fillable=['name','status','updated_at'];

    public function listData(){
        return Religion::pluck('name','religion_id')->toArray();
    }
}
