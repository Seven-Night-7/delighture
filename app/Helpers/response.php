<?php

//  统一响应
function json_response($statusCode, $data = [], $message = '')
{
    $message = $message ?
        $message : (isset(\App\Enums\StatusCode::$statusMessage[$statusCode]) ?
            \App\Enums\StatusCode::$statusMessage[$statusCode] : '未知状态码');

    return response()->json([
        'status_code' => $statusCode,
        'message' => $message,
        'data' => $data,
    ]);
}