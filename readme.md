# Delighture

1. 安装 Laravel 5.8，配置基础 `.env` 信息

2. 使用命令 `php artisan make:migration create_users_table` 创建基础用户表，迁移代码如下

database\migrations\2019_11_22_014057_create_users_table.php
```
public function up()
{
    Schema::create('users', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->string('account')->comment('账号');
        $table->string('password')->comment('密码');
        $table->unsignedTinyInteger('status')->default(0)->comment('账号状态 0:正常 1:冻结');
        $table->timestamps();
        $table->softDeletes();
    });
}
```

3. 定制自定义全局辅助函数模块，使用命令 `php artisan make:provider HelperServiceProvider` 创建 `HelperServiceProvider` ，其代码如下

app\Providers\HelperServiceProvider.php
```
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        foreach (glob(app_path('Helpers') . '/*.php') as $file) {
            require_once $file;
        }
    }
}
```

4. 在 `config/app.php` 中加入 `App\Providers\HelperServiceProvider::class`

config\app.php
```
'providers' => [
    .
    .
    .
    App\Providers\RouteServiceProvider::class,

    //  辅助函数
    App\Providers\HelperServiceProvider::class,

],
```

5. 考虑到 `dingo/api` 包不方便统一响应格式，决定还是自定义一个简单的响应逻辑。创建 `Helpers/response.php` 辅助函数文件，写入方法 `json_response()` 。其中 `App\Enums\StatusCode` 为自定义状态码类，后面会给出该类代码。代码如下

app\Helpers\response.php
```
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
```

6. 创建基础控制器 `BaseController` ,写入控制器统一响应方法 `response()`，后续所有的控制器都应该继承 `BaseController` ，代码如下

app\Http\Controllers\BaseController.php
```
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function response($statusCode = StatusCode::SUCCESS, $data = [], $message = '')
    {
        return json_response($statusCode, $data, $message);
    }
}
```

7. 紧接着我们利用枚举包来实现 `App\Enums\StatusCode` 自定义状态码类。包命令 `composer require bensampo/laravel-enum` ，安装完成后使用命令 `php artisan make:enum StatusCode` 便捷生成 `App\Enums\StatusCode` ，并定义一些已经用到的自定义状态码。（自己对枚举包的了解还比较浅显，后续会深入）代码如下

app\Enums\StatusCode.php
```
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

```

8. 下一步我们将实现基于JWT的token验证，实现步骤如下

- 包命令 `composer require tymon/jwt-auth:1.0.0-rc.4.1` （注意是Laravel 5.8版本对应的包，不同的Laravel版本对应的包的版本会不一样，需要自己去寻找）

- 生成 JWT 的 secret ，命令 `php artisan jwt:secret`

- 发布 JWT 配置文件，命令 `php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"` ，得到 `config/jwt.php` 配置文件

- 修改 `config/auth.php` 中 `api` 的指定驱动 `driver` 为 `jwt` ，以及后面的配置 `providers` 中 `users` 的指定模型 `model` 为 `App\Models\User::class` ，如下

config\auth.php
```
.
.
.
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],

    'api' => [
        'driver' => 'jwt',
        'provider' => 'users',
    ],
],
.
.
.
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],
],
.
.
.
```

- 创建 `App\Models\User::class` 模型，代码如下

app\Models\Model.php
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use SoftDeletes;

    protected $hidden = ['password'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
```

- 创建中间件 `TokenCheckMiddleware` 校验 token 有效性以及实现无痛刷新 token 。注意这里抛出的是自定义的异常响应，后续的异常也会用 `throw new HttpResponseException()` 来处理。代码如下

app\Http\Middleware\TokenCheckMiddleware.php
```
<?php

namespace App\Http\Middleware;

use App\Enums\StatusCode;
use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Http\Middleware\BaseMiddleware AS JWTBaseMiddleware;

class TokenCheckMiddleware extends JWTBaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try {
            $auth = JWTAuth::parseToken();
        } catch (JWTException $exception) {
            //  token不存在
            throw new HttpResponseException(json_response(StatusCode::MISSING_TOKEN));
        }

        if ($auth->check()) {
            //  token通过
            return $next($request);
        }

        try {
            //  刷新用户的 token
            $token = $this->auth->refresh();

            //  使用一次性登录以保证此次请求的成功
            Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);

        } catch (JWTException $exception) {
            // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
            throw new HttpResponseException(json_response(StatusCode::TOKEN_ERROR, [], $exception->getMessage()));
        }

        //  在响应头中返回新的 token
        return $this->setAuthenticationHeader($next($request), $token);
    }
}
```

- 在 `App\Http\Kernel` 中为中间件起别名，代码如下

app\Http\Kernel.php
```
.
.
.
protected $routeMiddleware = [
    .
    .
    .
    //  token校验
    'token.check' => \App\Http\Middleware\TokenCheckMiddleware::class
];
.
.
.
```

9. 创建控制器 `AuthenticationController` 实现登录 `store()` 和注销 `destroy()` ，以及相应的表单验证类 `AuthenticationRequest` 和表单验证基类 `FormRequest` 。代码如下

app\Http\Controllers\AuthenticationController.php
```
<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;
use App\Http\Requests\AuthenticationRequest;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthenticationController extends BaseController
{
    /**
     * 登录
     * @param AuthenticationRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(AuthenticationRequest $request)
    {
        $is_freeze = User::where('account', $request->account)->value('status');
        if ($is_freeze) {
            return $this->response(StatusCode::USER_IS_FROZEN);
        }

        $token = JWTAuth::attempt([
            'account' => $request->account,
            'password' => $request->password,
        ]);
        if (!$token) {
            return $this->response(StatusCode::LOGIN_ERROR);
        }

        return $this->response(StatusCode::SUCCESS, ['token' => 'bearer ' . $token], '登录成功');
    }

    /**
     * 注销登录
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        JWTAuth::parseToken()->invalidate();

        return $this->response(StatusCode::SUCCESS, [], '注销登录成功');
    }
}
```

app\Http\Requests\AuthenticationRequest.php
```
<?php

namespace App\Http\Requests;

class AuthenticationRequest extends FormRequest
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
                    'account' => 'required',
                    'password' => 'required|between:6,12',
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
```

app\Http\Requests\FormRequest.php
```
<?php

namespace App\Http\Requests;

use App\Enums\StatusCode;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest AS BaseFormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FormRequest extends BaseFormRequest
{
    public function authorize()
    {
        return true;
    }

    /**
     * 自定义验证失败处理
     * @param Validator $validator
     */
    public function failedValidation(Validator $validator)
    {
        $error_msg = $validator->errors()->first();

        throw new HttpResponseException(json_response(StatusCode::PARAM_ERROR, [], $error_msg));
    }
}
```

10. 此时表单验证在验证不通过时返回的英文提示，而我们需要的是中文提示，所以我们可以通过语言包 `overtrue/laravel-lang` 来解决这个问题。包命令 `composer require "overtrue/laravel-lang:~3.0"` ，安装完成后修改 `config/app.php` 中 `locale` 的值为 `zh-CN` ，以及将 `providers` 下的 `Illuminate\Translation\TranslationServiceProvider::class` 替换成 `Overtrue\LaravelLang\TranslationServiceProvider::class`，代码如下

config\app.php
```
.
.
.
'locale' => 'zh-CN',
.
.
.
'providers' => [
        .
        .
        .
        Illuminate\Session\SessionServiceProvider::class,
        Overtrue\LaravelLang\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        .
        .
        .
    ],
.
.
.
```

11. 创建全局辅助函数文件 `Helpers/user.php` ，创建方法 `me()` 使得我们可以在程序中快速获取登录用户的数据模型。接着创建控制器 `App\Http\Controllers\UserController` 及其方法 `me()` 作为获取登录用户数据的接口。代码如下

app\Helpers\user.php
```
<?php

//  获取我的用户数据（返回 App\Models\User 模型）
function me()
{
    return auth('api')->user();
}
```

app\Http\Controllers\UserController.php
```
<?php

namespace App\Http\Controllers;

use App\Enums\StatusCode;

class UserController extends BaseController
{
    /**
     * 我的登录信息
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return $this->response(StatusCode::SUCCESS, me());
    }
}
```

12. 前面登录、注销、获取登录用户信息的接口路由如下

routes\api.php
```
<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//  登录
Route::post('login', 'AuthenticationController@store');

Route::middleware('token.check')->group(function () {
    //  注销登录
    Route::delete('logout', 'AuthenticationController@destroy');
    //  我的登录信息
    Route::get('/users/me', 'UserController@me');
});
```

附
- 修改 `config/app.php` 中 `timezone` 值为 `Asia/Shanghai`
- 创建模型基类 `app\Model\Model.php` ，后续除 `App\Model\User::class` 以外的模型均继承该模型基类，代码如下

```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model AS BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class Model extends BaseModel
{
    use SoftDeletes;
}
```
