<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Groups extends Model
{
     use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'groups'; // Specify the custom table name
    protected $primaryKey = 'group_id';
    public $incrementing = false; // if primay key is string


     protected $fillable = [
       'group_id',
       'group_name',
       'group_details',
       'group_creator',
       'group_admins',
       'audience',
       'group_picture',
       'group_cover',
       'identifier',
    ];


    /* group_id → Foreign key for the Groups model inside users_has_groups.
user_id → Foreign key for the User model inside users_has_groups */

        //Get all group from specific users
    public function user(){
        return $this->belongsToMany('App\Models\User', 'users_has_groups', 'group_id', 'user_id');
    }

    //get group request
    public function profileUploadRequests()
    {
        return $this->morphMany(UploadRequest::class, 'uploadable', 'upload_request_type', 'upload_request_on_id')
            ->where('type', 'group_profile');
    }

    public function coverUploadRequests()
    {
        return $this->morphMany(UploadRequest::class, 'uploadable', 'upload_request_type', 'upload_request_on_id')
            ->where('type', 'group_cover');
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
