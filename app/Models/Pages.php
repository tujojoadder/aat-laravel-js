<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Pages extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'pages'; // Specify the custom table name
    protected $primaryKey = 'page_id';
    public $incrementing = false; // if primay key is string

    protected $fillable = [
        'page_id',
        'identifier',
        'page_name',
        'page_details',
        'page_creator',
        'page_admins',
        'page_picture',
        'page_cover',
        'category',
        'location',
        'phone',
        'email',

    ];
    public function upload()
    {
        return $this->morphMany(UploadRequest::class, 'uploadrequest_on');
    }


    //Get all group from specific users
    public function user()
    {
        return $this->belongsToMany('App\Models\User', 'users_has_pages', 'page_id', 'user_id');
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
