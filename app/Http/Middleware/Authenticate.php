<?php

namespace App\Http\Middleware;
use Illuminate\Support\Facades\Session;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        \Log::warning("JRN auth redirect", ["path" => $request->path(), "user" => optional(auth()->user())->id]);
        if (! $request->expectsJson()) {
            Session::put('previousUrl', url()->full());
            return $request->getSchemeAndHttpHost() . '/login';
        }
    }
}
