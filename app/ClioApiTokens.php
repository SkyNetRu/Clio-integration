<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClioApiTokens extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'token_type', 'access_token', 'expires_in', 'refresh_token',
    ];
}
