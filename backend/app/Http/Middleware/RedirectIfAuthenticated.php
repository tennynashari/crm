<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated as Middleware;

class RedirectIfAuthenticated extends Middleware
{
    /**
     * The URIs that should be accessible while authenticated.
     *
     * @var array<string>
     */
    protected $guards = [];
}
