<?php

namespace App\Http\Middleware;

use App\Enums\StatusCode;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware AS JWTBaseMiddleware;

class TokenCheckMiddleware extends JWTBaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $auth = JWTAuth::parseToken();
        } catch (JWTException $exception) {
            //  token不存在
            throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage(), null, StatusCode::MISSING_TOKEN);
        }

        if ($auth->check()) {
            //  token通过
            return $next($request);
        }

        try {
            //  刷新用户的 token
            $token = $this->auth->refresh();

            //  使用一次性登录以保证此次请求的成功
            Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);

        } catch (JWTException $exception) {
            // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
            throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage(), null, StatusCode::TOKEN_ERROR);
        }

        //  在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);
    }
}
