<?php

namespace Hwacom\EIPLogin\Services;


use Hwacom\ClientSso\Services\SSOService;
use Hwacom\PersonnelInfo\Services\EmployeeInfoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class EIPLoginService
{
    public function __construct()
    {
        $this->SSOService          = new SSOService();
        $this->EmployeeInfoService = new EmployeeInfoService();
    }

    /**
     * EIP後端登入API
     *
     * @param  Request  $request
     * @return false|\Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws ValidationException
     */
    public function loginEIP($data)
    {
        //組資料丟去EIP
        $url     = config('eip.eip_rul');
        $request = [
            //帳號欄位名稱 不同子系統可能不同(email登入)
            'userColumnName'        => $data['userColumnName'],
            //帳號欄位名稱 => user輸入的帳號
            $data['userColumnName'] => $data['username'],
            'password'              => $data['password'],
            'secret'                => config('eip.CLIENT_SECRET'),
            //唯一值抓cache用
            'unique'                => isset($_SERVER['HTTP_X_GOOG_AUTHENTICATED_USER_ID']) ? explode(":", $_SERVER['HTTP_X_GOOG_AUTHENTICATED_USER_ID'])[1] : $data['ip'],
        ];

        $response = self::postAPI($url, $request);
        $response = json_decode($response, true);
        //success
        //$response['data'] 回傳的token

        //error
        //$response['message']['type'] eip_login.throttle:登入錯誤次數過高鎖時間中 eip_login.failed:帳號密碼錯誤
        //$response['message']['seconds'] 帳號鎖定中(剩餘被鎖時間)
        //$response['message']['maxAttempts'] 錯誤限制次數(eip設定)

        if ($response && $response['success'] && $response['data']) { //回傳成功
            setcookie('token', $response['data'], time() + config('eip.JWT_EXP'), '/', config('eip.COOKIE_DOMAIN')); //把token設cookie

            $tokens = explode('.', $response['data']); //解碼token
            [$base64header, $base64payload, $sign] = $tokens;
            $payload = json_decode($this->SSOService->base64UrlDecode($base64payload));

            if ($payload->enumber) { //確定有enumber
                //同步user資料 UpdateOrCreate
                $user = $this->EmployeeInfoService->FetchUser($payload->enumber);
                Auth::login($user);
                $path = Session::get('redirect') ?? '/';
                return redirect($path);
            }

        } elseif ($response && $response['message'] && $response['message']['type'] == 'eip_login.throttle') { //登入次數過多
            throw ValidationException::withMessages([
                $data['userColumnName'] => [
                    trans($response['message']['type'], [
                        'seconds' => $response['message']['seconds'],
                        'minutes' => ceil($response['message']['seconds'] / 60), //剩餘鎖定時間
                    ]),
                ],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        } elseif ($response && $response['message'] && $response['message']['type'] == 'eip_login.failed') { //帳號密碼、user錯誤
            throw ValidationException::withMessages([
                $data['userColumnName'] => [
                    trans($response['message']['type'], [
                        'maxAttempts' => $response['message']['maxAttempts'], //錯誤次數限制依照eip
                    ]),
                ],
            ]);
        } else { //eip啥都沒回
            throw ValidationException::withMessages([
                $data['userColumnName'] => [trans('eip_login.eip_error')],
            ]);
        }

        return false;
    }

    /**
     * @param  string  $url
     * @param  array  $payload
     * @return string $result
     */
    public function postAPI($url = '', $payload = []): string
    {
        try {
            // 初始化 cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            # Return response instead of printing.
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            # Send request.
            $result = curl_exec($ch);
            curl_close($ch);

            return $result;
        } catch (\Exception $e) {
            return 'error：' . $e;
        }
    }
}

