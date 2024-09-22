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
class Unlikes extends Model
{
    
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'unlike'; // Specify the custom table name
    protected $primaryKey = 'unlike_id'; // Specify the custom primary key
    public $incrementing = false;
    protected $fillable = [
        'unlike_id',
        'unlike_on_type',
        'unlike_on_id',
        'unlike_by_id',
      
    ];
    public function unlike_on()
    {
        return $this->morphTo('unlike_on');
    }
    public function unliker()
    {
        return $this->belongsTo(User::class, 'unlike_by_id', 'user_id');
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
