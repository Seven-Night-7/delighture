<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;

class BaseController extends Controller
{
    /**
     * 控制器统一响应
     * @param int $statusCode
     * @param array $data
     * @param string $message
     * @return array
     */
    public function response($statusCode = StatusCode::SUCCESS, $data = [], $message = '')
    {
        return response_format($statusCode, $data, $message);
    }
}
