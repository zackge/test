<?php
/**
 * 钉钉发消息请求接口
 * ysz
 * 2017-11-06
 */
namespace App\Service\User;

use Auth;
use Cache;

class DdService
{
    /**
     * 发送钉钉消息
     *
     * @param unknown $touser
     *            发送钉钉用户ID
     * @param unknown $msg
     *            发送内容
     */
    public static function sendMsg($touser, $msg)
    {
        logger()->info('钉钉发消息信息:', ['touser' => [$touser], 'msg' => [$msg]]);
        if (Cache::get('access_token')) {
            $access_token = Cache::get('access_token');
        } else {
            $access_token = self::getToken();
            Cache::put('access_token', $access_token, 120);
        }
        //$access_token = self::getToken();
        logger()->info('钉钉发消息信息:', ['access_token' => [$access_token]]);
        $res='';
        if ($access_token) {
            $url = "https://oapi.dingtalk.com/message/send?access_token=" . $access_token;
            $agentid = env('DINGDING_AGENTID'); // 钉钉agentid
            logger()->info('钉钉发消息信息:', ['agentid' => [$agentid]]);
            $data = array(
                'touser' => $touser,
                'toparty' => '',
                'agentid' => $agentid,
                'msgtype' => 'text',
                'text' => array(
                    'content' => $msg
                )
            );
            $res = self::https_request($url, json_encode($data));
            \Log::info('钉钉结果'.$res);
        }

        return $res;
    }

    /**
     * 获取access_token
     */
    public static function getToken()
    {
        $corpid = env('DINGDING_CORPID'); // 钉钉后台corpid
        $corpsecret = env('DINGDING_CORPSECRET'); // 钉钉后台corpsecret
        $url = "https://oapi.dingtalk.com/gettoken?corpid=" . $corpid . "&corpsecret=" . $corpsecret;
        $data = file_get_contents($url);
        $out_array = json_decode($data, true);
        \Log::info($out_array);
        $access_token = '';
        if (isset($out_array["access_token"]) && $out_array["access_token"]) {
            $access_token = $out_array["access_token"];
        }

        return $access_token;
    }

    /**
     * curl请求
     *
     * @param
     *            $url
     * @param null $data
     * @return mixed
     * @throws \Exception
     */
    public static function https_request($url, $data = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if ($data) {
            // 模拟post请求
            curl_setopt($ch, CURLOPT_POST, 1);
            // 需要发送的数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            // 发送json数据
            if (is_string($data)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json; charset=utf-8',
                    'Content-Length: ' . strlen($data)
                ));
            }
        }
        $res = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        // 发生错误，抛出异常
        if ($error) {
            throw new \Exception('请求发生错误：' . $error);
        }

        return $res;
    }
}
