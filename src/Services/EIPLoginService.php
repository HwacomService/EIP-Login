<?php

namespace Hwacom\EIPLogin\Services;


use Hwacom\ClientSso\Services\SSOService;
use Hwacom\PersonnelInfo\Services\EmployeeInfoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
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
        $url     = config('eip.eip_url').'/api/login';
        $auth    = [
            'enumber'  => $data['username'],
            'password' => $data['password'],
        ];
        $auth    = Crypt::encrypt($auth);
        $request = [
            'auth'   => $auth,
            'secret' => config('eip.CLIENT_SECRET'),
            //唯一值抓cache用
            'unique' => isset($_SERVER['HTTP_X_GOOG_AUTHENTICATED_USER_ID']) ? explode(":", $_SERVER['HTTP_X_GOOG_AUTHENTICATED_USER_ID'])[1] : $data['ip'],
        ];

        $response = self::postAPI($url, $request);
        $response = json_decode($response, true);
        //success
        //$response['data'] 回傳的token

        if ($response && $response['success'] && $response['data']) { //回傳成功
            setcookie('token', $response['data'], time() + config('eip.JWT_EXP'), '/', config('eip.COOKIE_DOMAIN')); //把token設cookie

            $tokens = explode('.', $response['data']); //解碼token
            [$base64header, $base64payload, $sign] = $tokens;
            $payload = json_decode($this->SSOService->base64UrlDecode($base64payload));

            if ($payload->enumber) { //確定有enumber
                //同步user資料 UpdateOrCreate
                $user = config('eip.model')::where('enumber', $payload->enumber)->first();
                Auth::login($user, false);
                return true;
            }
        } elseif ($response && $response['message']) {
            throw ValidationException::withMessages(['enumber' => $response['message']]);
        } else {
            throw ValidationException::withMessages(['enumber' => 'EIP連線失敗']);
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

