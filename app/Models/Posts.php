<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Posts extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

     protected $table = 'posts'; // Specify the custom table name
     protected $primaryKey = 'post_id';
     public $incrementing = false;

     protected $fillable = [
         'post_id',
         'author_id',
         'group_id',
         'page_id',
         'timeline_ids',
         'audience',
         'post_type',
         'iaccount_id'
     ];
     public function comments()
     {
         return $this->hasMany(Comments::class, 'post_id', 'post_id');
     }

     public function textPost()
     {
         return $this->hasOne(TextPosts::class, 'post_id', 'post_id');
     }
     
     public function imagePost()
     {
         return $this->hasOne(ImagePosts::class, 'post_id', 'post_id');
     }
     
     public function author()
     {
         return $this->belongsTo(User::class, 'author_id', 'user_id');
     }
         // Define the relationship to the IAccount model
    public function iaccount()
    {
        return $this->belongsTo(IAccount::class, 'iaccount_id', 'iaccount_id');
    }
    public function likes()
    {
        return $this->morphMany(Likes::class, 'like_on');
    }

// Define the relationship to the Groups model
public function group()
{
    return $this->belongsTo(Groups::class, 'group_id', 'group_id');
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
