<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $table="notification";
    protected $primaryKey = 'notify_id';
    protected $fillable = [
        'subject','template','content','course','department','year','sendto','student','scheduled','scheduled_at','status','sent_status','sent_response','notify_id','created_at','updated_at','academic_year',
    ];

}
