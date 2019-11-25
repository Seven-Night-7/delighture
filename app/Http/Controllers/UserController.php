<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;

class UserController extends BaseController
{
    /**
     * 我的登录信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return $this->response(StatusCode::SUCCESS, me());
    }
}
