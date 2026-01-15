<?php

namespace app\common\middleware;

use think\Request;
use think\Response;

class BaseUserAuth
{
    public function handle(Request $request, \Closure $next): Response
    {
        $id = (int)$request->header('X-Base-User-Id', 0);
        if ($id > 0) {
            $request->withGet(['base_user_id' => $id]);
        } else {
            $sid = (string)$request->header('X-Base-User-Short-Id', '');
            if ($sid) {
                $request->withGet(['base_user_short_id' => $sid]);
            }
        }
        return $next($request);
    }
}

