<?php

namespace App\Exceptions;

use App\Enums\StatusCode;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        //  404异常处理
        if ($exception instanceof NotFoundHttpException) {
            return json_response(StatusCode::FAIL, [], '页面不存在');
        }

        //  路由模型绑定异常处理
        if ($exception instanceof ModelNotFoundException) {
            return json_response(StatusCode::MODEL_NOT_FOUND);
        }

        //  授权不通过异常处理，包含有：JWT认证
        if ($exception instanceof UnauthorizedHttpException) {
            return json_response($exception->getCode(), [], $exception->getMessage());
        }

        return parent::render($request, $exception);
    }
}
