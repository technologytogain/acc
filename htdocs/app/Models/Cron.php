<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cron extends Model{
    
    use HasFactory;

    protected $table="cron";
    protected $primaryKey = 'cron_id';
}
