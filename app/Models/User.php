<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = [
        'name',
        'email',
        'username',
        'password',
        'token',
        'contact',
        'avatar',
        'facebook_id',
    ];
    public function scopeTable($query)
    {
        return $query -> from('users as u');
    }
    public function scopeByUserName($query, $username)
    {
        return $query -> where("u.username", $username);
    }
    public function scopeByEmail($query, $email)
    {
        return $query -> where('u.email', $email);
    }
}
