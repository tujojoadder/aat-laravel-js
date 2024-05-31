<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Replies extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'replies'; // Specify the custom table name
     protected $primaryKey = 'reply_id'; // Specify the custom primary key
     public $incrementing = false;
     protected $fillable = [
         'reply_id',
         'comment_id',
         'parent_reply_id',
         'replied_by_id',
         'reply_text',
         'is_reported',
         'reported_count',

     ];



     public function comment()
     {
         return $this->belongsTo(Comments::class, 'comment_id', 'comment_id');
     }
 
     public function parentReply()
     {
         return $this->belongsTo(Replies::class, 'parent_replay_id', 'reply_id');
     }
 
     public function repliedBy()
     {
         return $this->belongsTo(User::class, 'replied_by_id', 'user_id');
     }
 
     public function replies()
     {
         return $this->hasMany(Replies::class, 'parent_replay_id', 'reply_id');
     }

     public function likes()
     {
         return $this->morphMany(Likes::class, 'like_on');
     }

     public function reports()
     {
         return $this->morphMany(Report::class, 'reportable');
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
