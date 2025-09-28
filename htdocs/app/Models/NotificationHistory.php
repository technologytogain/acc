<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationHistory extends Model{
    
    use HasFactory;

    protected $table="notification_history";
    protected $primaryKey = 'notifyh_id';
    public $timestamps = false;
}
