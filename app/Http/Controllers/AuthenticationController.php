<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;
use App\Http\Requests\AuthenticationRequest;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationController extends BaseController
{
    /**
     * 登录
     * @param AuthenticationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AuthenticationRequest $request)
    {
        $is_freeze = User::where('account', $request->account)->value('status');
        if ($is_freeze) {
            return $this->response(StatusCode::USER_IS_FROZEN);
        }

        $token = JWTAuth::attempt([
            'account' => $request->account,
            'password' => $request->password,
        ]);
        if (!$token) {
            return $this->response(StatusCode::LOGIN_ERROR);
        }

        return $this->response(StatusCode::SUCCESS, ['token' => 'bearer ' . $token], '登录成功');
    }

    /**
     * 注销登录
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        JWTAuth::parseToken()->invalidate();

        return $this->response(StatusCode::SUCCESS, [], '注销登录成功');
    }
}
