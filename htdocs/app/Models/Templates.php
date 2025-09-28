<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Templates extends Model
{
    use HasFactory;

    protected $table="m_templates";
    protected $primaryKey = 'template_id';

    public function listData(){
        return Templates::pluck('subject','template_id')->toArray();
    }

}
