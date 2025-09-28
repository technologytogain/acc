<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $table="m_state";
    protected $primaryKey = 'state_id';

    public $fillable=['name','state','updated_at'];

     public function listData(){
        return State::pluck('name','state_id')->toArray();
    }
}
