<?php

//  获取我的用户数据（返回 App\Models\User 模型）
function me()
{
    return auth('api')->user();
}