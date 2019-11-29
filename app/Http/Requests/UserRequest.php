<?php

namespace App\Http\Requests;

class UserRequest extends FormRequest
{
    /**
     * 验证规则
     * @return array
     */
    public function rules()
    {
        switch ($this->method())
        {
            case 'POST':
                return [
                    'account' => 'required|unique:users',
                    'password' => 'required|between:6,12|confirmed',
                ];
        }
    }

    /**
     * 属性名称
     * @return array
     */
    public function attributes()
    {
        return [
            'account' => '账号',
            'password' => '密码',
        ];
    }
}
