<?php

namespace App\Http\Requests;

use App\Enums\StatusCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest AS BaseFormRequest;
use Illuminate\Validation\ValidationException;

class FormRequest extends BaseFormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * 自定义验证失败处理
     * @param Validator $validator
     * @throws ValidationException
     */
    public function failedValidation(Validator $validator)
    {
        $error_msg = $validator->errors()->first();

        throw new ValidationException($validator, json_response(StatusCode::PARAM_ERROR, [], $error_msg));
    }
}
