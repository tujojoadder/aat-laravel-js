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
class Loves extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'love'; // Specify the custom table name
     protected $primaryKey = 'love_id'; // Specify the custom primary key
     public $incrementing = false;
     protected $fillable = [
         'love_id',
         'love_on_type',
         'love_on_id',
         'love_by_id',
       
     ];
     public function love_on()
     {
         return $this->morphTo('love_on');
     }
     public function lover()
     {
         return $this->belongsTo(User::class, 'love_by_id', 'user_id');
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
