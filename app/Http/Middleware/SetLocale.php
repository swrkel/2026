<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $locale = setting('default_lang', 'en');

        if (isAppInstalled()) {
            $availableLanguages = availableLanguages();
            $segmentLocale = (string) Request::segment(1);

            if ($segmentLocale !== '' && array_key_exists($segmentLocale, $availableLanguages)) {
                $locale = $segmentLocale;
            } else {
                if (Auth::guard($guard)->check()) {
                    $userLocale = Auth::user()->locale;
                    if ($userLocale) {
                        $locale = $userLocale;
                    }
                } elseif ($request->hasCookie('locale')
                    && array_key_exists($request->cookie('locale'), $availableLanguages)) {
                    $locale = $request->cookie('locale');
                }
            }

            // if locale if different or not set send locale cookie
            if( ! $request->hasCookie('locale') || $request->cookie('locale') != $locale){
                Cookie::queue('locale', $locale, 43200);    
                // return redirect()->back();
            }
            
            app()->setLocale( $locale );
            url()->defaults(['locale' => $locale]);

            date_default_timezone_set(setting('timezone', 'UTC'));

            if ($request->route()) {
                $request->route()->forgetParameter('locale');
            }

            
        }

        return $next($request);
    }
}
