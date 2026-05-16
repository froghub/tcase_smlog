<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = ['batch_id', 'recipient_id', 'channel', 'message', 'status'];
}
