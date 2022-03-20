<?php
namespace App\Http\Middleware;
use Closure;

class LangMiddleware
{
    public function handle($request, Closure $next)
    { 
		$localization 	= @$request->header('Accept-Language');
		if(@$localization == "id"){
			app('translator')->setLocale("id");
		}else{
            app('translator')->setLocale("en");
        }
        return $next($request);
 
    }
	 
}