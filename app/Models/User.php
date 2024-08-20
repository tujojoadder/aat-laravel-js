<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use PhpParser\Node\Stmt\GroupUse;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'users'; // Specify the custom table name
    protected $primaryKey = 'user_id';
    public $incrementing = false; // if primay key is string


    protected $fillable = [
        'user_id',
        'profile_picture',
        'user_fname',
        'user_lname',
        'email',
        'password',
        'gender',
        'privacy_setting',
        'birthdate',
    
        'identifier',
        'blueticks',
        'cover_photo'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'privacy_setting',
    ];

    public function post()
    {
        return $this->hasMany(Posts::class,'author_id' );
    }
    //Created groups
    public function group()
    {
        return $this->hasMany(Groups::class,'group_creator' );
    }
    public function page()
    {
        return $this->hasMany(Pages::class,'page_creator' );
    }
    public function iaccount()
    {
        return $this->hasMany(IAccount::class,'iaccount_creator' );
    }
    public function comment()
    {
        return $this->hasMany(Comments::class,'commenter_id' );
    }
    public function reply()
    {
        return $this->hasMany(Replies::class,'replied_by_id' );
    }

  



    //for retrive friend requests user has
    public function friendRequest()
    {
        return $this->hasMany(FriendRequest::class, 'receiver_id', 'user_id');
    }

    //Get users groups
    public function groups()
    {
        return $this->belongsToMany('App\Models\Groups', 'users_has_groups', 'user_id', 'group_id');
    }
    //Get users pages
    public function pages()
    {
        return $this->belongsToMany('App\Models\Pages', 'users_has_pages', 'user_id', 'page_id');
    }
    public function upload()
    {
        return $this->morphMany(UploadRequest::class, 'uploadrequest_on');
    }
    //Retrive how many iaccount user following
    public function iAccountFollowers()
    {
        return $this->hasMany(iAccountFollowers::class, 'follower_id');
    }
    public function comments()
    {
        return $this->hasMany(Comments::class, 'commenter_id', 'user_id');
    }

    public function reports()
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function dayHadith()
    {
        return $this->hasOne(DayHadith::class, 'user_id', 'user_id');
    }




      // Define the relationship to retrieve the followers
      public function followers()
      {
          return $this->hasMany(UserFollower::class, 'user_id', 'user_id');
      }
  


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
