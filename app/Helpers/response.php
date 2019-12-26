<?php

//  统一响应格式
function response_format($statusCode, $data, $message)
{
    return [
        'status_code' => $statusCode,
        'message' => $message ? $message : \App\Enums\StatusCode::getStatusMessage($statusCode),
        'data' => $data,
    ];
}

//  快捷响应
function json_response($statusCode, $data = [], $message = '')
{
    return response()->json(response_format($statusCode, $data, $message));
}