<?php


namespace fize\third\baidu;

use RuntimeException;
use fize\net\Http;
use fize\crypt\Json;


/**
 * 百度LBS接口请求基类
 */
class Map
{

    /**
     * @var string 应用AK
     */
    protected $ak = "";

    /**
     * @var string sk
     */
    protected $sk = null;

    /**
     * 主URL域名
     */
    protected static $DOMAIN_API = "http://api.map.baidu.com";

    /**
     * 实例化
     * @param string $ak 应用AK
     * @param string $sk SK
     */
    public function __construct($ak, $sk = null)
    {
        $this->ak = $ak;
        $this->sk = $sk;
    }

    /**
     * 获取SN
     * @param string $sk                 SK值
     * @param string $url                请求URL
     * @param array  $querystring_arrays 请求参数
     * @param string $method             请求方式;GET/POST
     * @return string
     */
    protected function caculateAKSN($sk, $url, array $querystring_arrays, $method = 'GET')
    {
        foreach ($querystring_arrays as $key => $value) {
            if (is_object($value)) {
                unset($querystring_arrays[$key]); //对象类(一般为CURLFile)无需参与SN加密
            }
        }
        if ($method === 'POST') {
            ksort($querystring_arrays);
        }
        $querystring = http_build_query($querystring_arrays);

        if ($method === 'GET') {
            $querystring = str_replace('%2C', ',', $querystring);  //替换字符串[,]
        }

        return md5(urlencode($url . '?' . $querystring . $sk));
    }

    /**
     * 内部使用的统一GET方法
     * @param string  $uri     资源标识
     * @param array   $paras   GET参数
     * @param mixed   $out     输出的参数，如果是字符串表示输出该字符串键名的对应内容，是数组表示输出以这些键名组成的对应内容
     * @param boolean $ak_must AK值是否是必须传递的，默认true，如果设置为true时，会在参数中检查ak值是否指定，并在未指定时加入系统ak
     * @param string  $sn_key  要加入的sn键名，默认为sn，正常情况下不需要修改该值
     * @return mixed
     */
    protected function httpGet($uri, array $paras, $out, $ak_must = true, $sn_key = 'sn')
    {
        if ($ak_must && !isset($paras['ak'])) {
            $paras['ak'] = $this->ak;
        }
        if (!is_null($this->sk)) {
            $sn = $this->caculateAKSN($this->sk, $uri, $paras);
            $paras[$sn_key] = $sn;
        }

        $paras_str = http_build_query($paras);
        $paras_str = str_replace('%2C', ',', $paras_str);  //替换字符串[,]

        $url = self::$DOMAIN_API . $uri . "?" . $paras_str;

        $json = Http::get($url);

        $array = Json::decode($json);

        if ($array === false) {
            throw new RuntimeException(Json::lastErrorMsg(), Json::lastError());
        }

        if (is_string($out)) { //字符串作为单独返回的字段
            return isset($array[$out]) ? $array[$out] : null;
        } else { //数组作为返回多个带键名
            $result = [];
            foreach ($out as $key) {
                $result[$key] = isset($array[$key]) ? $array[$key] : null;
            }
            return $result;
        }
    }

    /**
     * 内部使用的统一POST方法
     * @param string  $uri     资源标识
     * @param array   $paras   GOST参数
     * @param mixed   $out     输出的参数，如果是字符串表示输出该字符串键名的对应内容，是数组表示输出已这些键名组成的对应内容
     * @param boolean $ak_must AK值是否是必须传递的，默认true，如果设置为true时，会在参数中检查ak值是否指定，并在未指定时加入系统ak
     * @param string  $sn_key  要加入的sn键名，默认为sn，正常情况下不需要修改该值
     * @return mixed
     */
    protected function httpPost($uri, array $paras, $out, $ak_must = true, $sn_key = 'sn')
    {
        if ($ak_must && !isset($paras['ak'])) {
            $paras['ak'] = $this->ak;
        }
        if (!is_null($this->sk)) {
            $sn = $this->caculateAKSN($this->sk, $uri, $paras, 'POST');
            $paras[$sn_key] = $sn;
        }

        $url = self::$DOMAIN_API . $uri;

        $json = Http::post($url, $paras);

        $array = Json::decode($json);

        if ($array === false) {
            throw new RuntimeException(Json::lastErrorMsg(), Json::lastError());
        }

        if (is_string($out)) { //字符串作为单独返回的字段
            return isset($array[$out]) ? $array[$out] : null;
        } else { //数组作为返回多个带键名
            $result = [];
            foreach ($out as $key) {
                $result[$key] = isset($array[$key]) ? $array[$key] : null;
            }
            return $result;
        }
    }
}
