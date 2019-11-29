<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    /**
     * 我的登录信息
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return $this->response(StatusCode::SUCCESS, $request->user());
    }
}
