<?php

namespace App;

use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, SoftDeletes;

    protected $dates = ['deleted_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nick',
        'profile_id',
        'name',
        'email',
        'password',
        'description',
        'image',
        'enabled',
        'verified',
        'verification_email_token'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'verification_email_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function setNickAttribute($value)
    {
        $this->attributes['nick'] = strtolower($value);
    }


    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    public function setImageAttribute($value)
    {
        $data = explode(',', $value);
        $this->attributes['mime'] = $data[0];
        $this->attributes['image'] = base64_decode($data[1]);
    }


    public function getImageAttribute($value)
    {
        if (isset($value)) {
            $mime = $this->attributes['mime'];
            $data = base64_encode($value);
            return $mime.','.$data;
        }
        else {
            return null;
        }
    }
    

    public static function generateEmailToken()
    {
        return str_random(45);
    }


    /**
     * Relationship with profiles table
     * @return Profile
     */
    public function profile()
    {
        return $this->belongsTo('App\Profile');
    }


    /**
     * Relationship with posts table
     * @return Post
     */
    public function post()
    {
        return $this->hasMany('App\Post');
    }


    /**
     * Relationship with likes table
     * @return Like
     */
    public function like()
    {
        return $this->hasMany('App\Like');
    }


    /**
     * Relationship with comments table
     * @return Comment
     */
    public function comment()
    {
        return $this->hasMany('App\Comment');
    }


    /**
     * Relationship with follows table
     * @return User
     */
    public function following()
    {
        return $this->belongsToMany('App\User', 'follows', 'user_id', 'following');
    }


    /**
     * Relationship with follows table
     * @return User
     */
    public function follower()
    {
        return $this->belongsToMany('App\User', 'follows', 'following', 'user_id');
    }

    /**
     * Check if the user has one of the roles
     * @param array $roles
     * @return boolean
     */
    public function hasProfile(array $profiles)
    {
        $userProfile = $this->profile()->get()->pluck('profile')->first();
        foreach ($profiles as $profile) {
            if ($userProfile == $profile) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if it's a admin user
     * @return boolean
     */
    public function isAdmin()
    {
        return $this->hasProfile(['admin']);
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
