<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\Relation;
Relation::enforceMorphMap([
    'post'=>'App\Models\Posts',
    'comment'=>'App\Models\Comments',
    'reply'=>'App\Models\Replies',

]);
class Likes extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'likes'; // Specify the custom table name
     protected $primaryKey = 'like_id'; // Specify the custom primary key
     public $incrementing = false;
     protected $fillable = [
         'like_id',
         'like_on_type',
         'like_on_id',
         'like_by_id',
         'reaction_type',
     ];
     public function like_on()
     {
         return $this->morphTo('like_on');
     }
     public function liker()
     {
         return $this->belongsTo(User::class, 'like_by_id', 'user_id');
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
