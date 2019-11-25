<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class StatusCode extends Enum
{
    const SUCCESS = 0;
    const FAIL  = -1;

    const PARAM_ERROR = -10000;

    const LOGIN_ERROR = -20001;
    const USER_IS_FROZEN = -20002;

    const TOKEN_ERROR = -30000;
    const MISSING_TOKEN = -30001;

    public static $statusMessage = [
        0      => '请求成功',
        -1     => '请求失败',

        -10000 => '参数验证错误',

        -20001 => '账号或密码错误',
        -20002 => '账号已被冻结',

        -30000 => 'token异常',
        -30001 => 'token不存在',
    ];
}
