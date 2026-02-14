<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $roles,$access_url)
    {

        $roles = explode("|", $roles);

        if(in_array($request->user()->user_type, $roles)){
            if($request->user()->user_type=='admin')
            {
              return $next($request);

            }
            else{

                $staff= \App\Staff::select('access_level')->where('user_id',$request->user()->id)->first()->toArray();
                $access_level= json_decode($staff['access_level'],true);

                if($access_url=='view_all')
                {
                    if($access_level['view_all']==1 or $access_level['edit']==1)
                    {
                         return $next($request);
                    }
                    else{
                         abort(401);
                    }
                }

                else if($access_url=='edit')
                {
                    if($access_level['edit']==1)
                    {
                         return $next($request);
                    }
                    else{
                         abort(401);
                    }
                }
                else if($access_url=='one_day_view'){
                    if($access_level['one_day_view']==1)
                    {
                         return $next($request);
                    }
                    else{
                         abort(401);
                    }
                }
                else{
                    abort(401);
                }
            }
        }
        else{
          abort(401);
        }


    }
}
