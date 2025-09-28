<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YearUpgrade extends Model
{
    use HasFactory;

    protected $table="year_upgrade";
    protected $primaryKey = 'upgrade_id';
}
