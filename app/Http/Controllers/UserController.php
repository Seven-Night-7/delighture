<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;
use App\Http\Requests\UserRequest;
use App\Models\User;

class UserController extends BaseController
{
    /**
     * 列表
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return $this->response(StatusCode::SUCCESS, User::all());
    }

    /**
     * 新增
     * @param UserRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(UserRequest $request)
    {
        User::create([
            'account' => $request->account,
            'password' => bcrypt($request->password),
        ]);

        return $this->response();
    }

    /**
     * 冻结用户
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function freeze(User $user)
    {
        $user->status = 1;
        $user->save();

        return $this->response();
    }

    /**
     * 解冻用户
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function unfreeze(User $user)
    {
        $user->status = 0;
        $user->save();

        return $this->response();
    }
}
