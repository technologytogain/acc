<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportClone extends Model
{
    use HasFactory;

    protected $table="import_clone";
    protected $primaryKey = 'iclone_id';

}
