<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Community extends Model
{
    use HasFactory;

    protected $table="m_community";
    protected $primaryKey = 'community_id';

    public $fillable=['name','status','updated_at'];

    public function listData(){
        return Community::pluck('name','community_id')->toArray();
    }

}
