# SSO Client Package via Hwacom SSO

<a href="https://github.com/mozielin/Client-SSO/actions"><img src="https://github.com/mozielin/Client-SSO/workflows/PHP Composer/badge.svg" alt="Build Status"></a>
[![Total Downloads](http://poser.pugx.org/hwacom/client-sso/downloads)](https://packagist.org/packages/hwacom/client-sso)
[![Latest Stable Version](http://poser.pugx.org/hwacom/client-sso/v)](https://packagist.org/packages/hwacom/client-sso)
## 前言

要用我華電SSO必先安裝客戶端

## 安裝說明

```bash
composer require hwacom/eip-login
```

## Service Provider設定 (Laravel 5.5^ 會自動掛載)

Composer安裝完後要需要修改 `config/app.php` 找到 providers 區域並添加:

```php
\Hwacom\SSO\SSOServiceProvider::class,
```

## Config設定檔發佈 

用下列指定會建立sso.php設定檔，需要在 `.env` 檔案中增加設定.

```bash
php artisan vendor:publish
```

 下列設定會自動增加在 `config/eip.php`

```php
'eip_auth' => env('EIP_AUTH', false),
'eip_rul' => env('EIP_URL'),
'JWT_EXP' => env('JWT_EXP', 900),
'CLIENT_SECRET' => env('EIP_CLIENT_SECRET'),
'COOKIE_DOMAIN' => env('COOKIE_DOMAIN'),
```

在`.env` 中增加設定

```php
EIP_AUTH        = true
EIP_URL         = 
CLIENT_SECRET   =
COOKIE_DOMAIN   =
```

## [LoginController] 增加兩個Function
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
                'username'       => $request->帳號欄位,
                'password'       => $request->password,
                'userColumnName' => $this->username(),
            ];
            $this->loginService->loginEIP($data);
    }
    
    $this->login($request); //一般登入
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
    Auth::guard('web')->logout();

    $request->session()->invalidate();

    $request->session()->regenerateToken();
    
    setcookie("token", "", time() - 3600, '/', config('eip.COOKIE_DOMAIN'));

    return redirect(config("sso.sso_host"));
}
```
## [Middleware] 增加至`Http/Kernel.php`web Group中

```php
\Hwacom\ClientSso\Middleware\SSOAuthenticated::class,
```
