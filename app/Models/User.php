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
        'image',
        'sunscription',
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
    public function scopeById($query, $id)
    {
        return $query -> where('u.id', $id);
    }
    public function scopeByToken($query, $token)
    {
        return $query -> where('u.token', $token);
    }
    public function scopeOfType($query, $subscription)
    {
        return $query -> where('subscription', $subscription);
    }
    public function scopeNotSubscribed($query)
    {
        return $query -> where(function($q){
            $q -> where('u.subscription', '')
               -> orWhereNull('u.subscription');
        });
    }
    public function scopeActive($query)
    {
        return $query -> where('status', 1);
    }
}
