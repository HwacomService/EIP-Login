# EIP Login Package via Hwacom

<a href="https://github.com/mozielin/Client-SSO/actions"><img src="https://github.com/mozielin/Client-SSO/workflows/PHP Composer/badge.svg" alt="Build Status"></a>
[![Total Downloads](http://poser.pugx.org/hwacom/client-sso/downloads)](https://packagist.org/packages/hwacom/client-sso)
[![Latest Stable Version](http://poser.pugx.org/hwacom/client-sso/v)](https://packagist.org/packages/hwacom/client-sso)

## 安裝說明

```bash
composer require hwacom/eip-login
```

## 需提前安裝並設定的套件

laravel/breeze

```
php artisan breeze:install
```

```
npm uninstall tailwindcss postcss autoprefixer
```

```
npm install tailwindcss@npm:@tailwindcss/postcss7-compat @tailwindcss/postcss7-compat postcss@^7 autoprefixer@^9
```

```
npm install
```

```
npm run dev
```

```
php artisan migrate
```

<br>SSO 登入<br>
<a href="https://github.com/HwacomService/SSO-Client">hwacom/client-sso</a>
<br><br>人員資料 HRepository<br>
<a href="https://github.com/HwacomService/Personnel-Info">hwacom/personnel-info</a>

## Service Provider設定 (Laravel 5.5^ 會自動掛載)

Composer安裝完後要需要修改 `config/app.php` 找到 providers 區域並添加:

```php
\Hwacom\EIPLogin\EIPLoginServiceProvider::class,
```

## Config設定檔發佈

用下列指令會建立eip.php設定檔，需要在 `.env` 檔案中增加設定， 同時建立出eip_login語系檔

```bash
php artisan vendor:publish
```

下列設定會自動增加在 `config/eip.php`

```php
'eip_auth' => env('EIP_AUTH', false),
'eip_url' => env('EIP_URL'),
'JWT_EXP' => env('JWT_EXP', 900),
'CLIENT_SECRET' => env('EIP_CLIENT_SECRET'),
'COOKIE_DOMAIN' => env('COOKIE_DOMAIN'),
```

在`.env` 中增加設定

```php
EIP_AUTH          = true
EIP_URL           = 
EIP_CLIENT_SECRET =
COOKIE_DOMAIN     =
```

## [LoginController] 增加兩個Function

```
use Hwacom\EIPLogin\Services\EIPLoginService;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
```

```
class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function __construct()
    {
        $this->loginService = new EIPLoginService();
    }
```

增加function

```
public function username()
{
    return 'enumber'; //帳號欄位名
}
```

Login

```
/**
 * 進入login前function 判斷走login/loginEIP
 *
 */
public function store()
{
    if (config('eip.eip_auth')) { //EIP登入
            $data = [
                'ip'             => $request->ip(),
                'username'       => $request->enumber,
                'password'       => $request->password,
            ];
            $this->loginService->loginEIP($data);
    }
    
    $this->login($request); //一般登入

    $request->session()->regenerate();

    return redirect()->intended(RouteServiceProvider::HOME);
}
```

Logout

```
/**
 * 登出用需自行寫入LoginController中
 *
 * @param  \Illuminate\Http\Request  $request
 * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
 */
public function destroy(Request $request)
{
    if (config('sso.sso_enable') === true) {
        setcookie("token", "", time() - 3600, '/', '.hwacom.com');
    }

    Auth::guard('web')->logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();
    
    setcookie("token", "", time() - 3600, '/', config('eip.COOKIE_DOMAIN'));

    return redirect(config("sso.sso_host"));
}
```

## [LoginRequest] 調整rules

```
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if (!config('eip.eip_auth')) {
            return [
                'enumber'  => 'required|string',
                'password' => 'required|string',
            ];
        } else {
            return [];
        }
    }
```

## [login.blade] 調整

帳號input調整

```
   <div>
        <x-label for="enumber" :value="__('工號')" />
    
        <x-input id="enumber" class="block mt-1 w-full" type="text" name="enumber" :value="old('enumber')" required autofocus />
   </div>
```