<?php

namespace App\Http\Middleware;

use App\Enums\StatusCode;
use Closure;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware AS JWTBaseMiddleware;

class RefreshToken extends JWTBaseMiddleware
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
        //  Step1：检测token字段是否存在
        if (! $this->auth->parser()->setRequest($request)->hasToken()) {
            throw new UnauthorizedHttpException('jwt-auth', null, null, StatusCode::MISSING_TOKEN);
        }

        try {
            //  Step2：检测用户是否登录
            $user = $this->auth->parseToken()->authenticate();
            if ($user) {

                //  Step3：检测用户账号是否被冻结
                if ($user->status == 1) {
                    throw new UnauthorizedHttpException('jwt-auth', null, null, StatusCode::USER_IS_FROZEN);
                }

                //  token认证通过
                return $next($request);
            }

            throw new UnauthorizedHttpException('jwt-auth', null, null, StatusCode::USER_IS_NOT_LOGGED);

        } catch (TokenExpiredException $exception) {

            //  token已过期，尝试刷新token

            try {
                //  Step4：刷新用户的 token
                $token = $this->auth->refresh();

                //  Step5：使用一次性登录以保证此次请求的成功
                Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);

            } catch (JWTException $exception) {

                //  如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
                throw new UnauthorizedHttpException('jwt-auth', $exception->getMessage(), null, StatusCode::TOKEN_ERROR);
            }
        }

        //  Step6：在响应头中返回新的token
        return $this->setAuthenticationHeader($next($request), $token);
    }
}
