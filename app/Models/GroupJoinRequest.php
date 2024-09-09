<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class GroupJoinRequest extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'group_join_requests'; // Specify the custom table name
    protected $primaryKey = 'group_request_id';
    public $incrementing = false; // if primay key is string


    protected $fillable = [
        'group_request_id',
        'sender_id',
        'group_id',
     

    ];
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    /*  protected $hidden = [
        'password',
        'remember_token',
    ];
 */
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
