<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * 自定义异常状态码
 * Class StatusCode
 * @package App\Enums
 */
final class StatusCode extends Enum
{
    const SUCCESS = 0;
    const FAIL  = -1;

    const PARAM_ERROR = -10000;
    const MODEL_NOT_FOUND = -10001;

    const LOGIN_ERROR = -20001;
    const USER_IS_FROZEN = -20002;
    const USER_IS_NOT_LOGGED = -20003;

    const TOKEN_ERROR = -30000;
    const MISSING_TOKEN = -30001;

    public static $statusMessage = [
        0      => '请求成功',
        -1     => '请求失败',

        -10000 => '参数验证错误',
        -10001 => 'id对应的模型不存在',

        -20001 => '账号或密码错误',
        -20002 => '账号已被冻结',
        -20003 => '用户未登录',

        -30000 => 'token异常',
        -30001 => 'token不存在',
    ];
}
