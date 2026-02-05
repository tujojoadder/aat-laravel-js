<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class FriendRequest extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'friend_requests'; // Specify the custom table name
     protected $primaryKey = 'friend_request_id'; // Specify the custom primary key
     public $incrementing = false;


     protected $fillable = [
         'friend_request_id',
         'sender_id',
         'receiver_id',
         'status'
     ];
  // Define the inverse of the relationship in User model
  public function user()
  {
      return $this->belongsTo(User::class,'receiver_id', 'user_id');
  }

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
