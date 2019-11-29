<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;
use App\Http\Requests\AuthenticationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        $token = Auth::attempt($request->only('account','password'));
        if (!$token) {
            return $this->response(StatusCode::LOGIN_ERROR);
        }

        return $this->response(StatusCode::SUCCESS, ['token' => 'Bearer ' . $token], '登录成功');
    }

    /**
     * 注销登录
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        Auth::logout();

        return $this->response(StatusCode::SUCCESS, [], '注销登录成功');
    }

    /**
     * 获取我的登录信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return $this->response(StatusCode::SUCCESS, $request->user());
    }
}
