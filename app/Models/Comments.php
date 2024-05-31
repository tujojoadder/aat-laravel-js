<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Comments extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'comments'; // Specify the custom table name
     protected $primaryKey = 'comment_id';
     public $incrementing = false;
  
     protected $fillable = [
         'comment_id',
         'post_id',
         'commenter_id',
         'comment_text',


     ];


     public function commenter()
     {
         return $this->belongsTo(User::class, 'commenter_id', 'user_id');
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
