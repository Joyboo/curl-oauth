<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */

namespace OAuth;

abstract class BaseOAuth
{
    protected $config;

    protected $accessTokenResult = [];

    protected $refreshTokenResult = [];

    public function __construct(BaseConfig $config)
    {
        $this->config = $config;
    }

    protected function getUrl($url, $params = [])
    {
        return empty($params) ? $url : ($url . '?' . http_build_query($params));
    }

    public function getAccessToken($storeState = null, $state = null, $code = null)
    {

        if (!$this->checkState($storeState, $state)) {
            throw new OAuthException('state 验证失败');
        }

        return $this->__getAccessToken($state, $code);

    }

    private function checkState($storeState = null, $state = null)
    {
        if (empty($storeState) && empty($state)) {
            return true;
        }

        if ($storeState != $state) {
            return false;
        }

        return true;
    }

    protected function jsonp_decode(string $jsonp, $assoc = true)
    {
        $jsonp = trim($jsonp);
        if (isset($jsonp[0]) && $jsonp[0] !== '[' && $jsonp[0] !== '{') {
            $begin = strpos($jsonp, '(');
            if (false !== $begin) {
                $end = strrpos($jsonp, ')');
                if (false !== $end) {
                    $jsonp = substr($jsonp, $begin + 1, $end - $begin - 1);
                }
            }
        }
        return json_decode($jsonp, $assoc);
    }

    /**
     * @return array
     */
    public function getAccessTokenResult(): array
    {
        return $this->accessTokenResult;
    }

    /**
     * @return array
     */
    public function getRefreshTokenResult(): array
    {
        return $this->refreshTokenResult;
    }

    public abstract function getAuthUrl();

    protected abstract function __getAccessToken($state = null, $code = null);

    public abstract function getUserInfo(string $accessToken);

    public abstract function refreshToken(string $refreshToken = null);

    public abstract function validateAccessToken(string $accessToken);

    public function curl($url, $params ='', $return = 1, $header = [], $cookie = [], $option = [])
    {
        $ch = curl_init($url); //初始化curl并设置链接
        //curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        //设置是否为post传递
        curl_setopt($ch, CURLOPT_POST, (bool)$params);
        //对于https 设定为不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, $return);//设置是否返回信息

        if($cookie)
        {
            $key = array_keys($cookie);
            curl_setopt($ch, $key[0]=='jar' ? CURLOPT_COOKIEJAR : CURLOPT_COOKIEFILE, $cookie['file']);
        }

        if($params)
        {
            if(is_array($params))
            {
                $params = http_build_query($params);
            }
            //POST 数据
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        }

        if($header)
        {
            foreach($header as $k => $v)
            {
                $newheader[] = is_numeric($k) ? $v : "$k: $v";
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $newheader); //设置头信息的地方
        }
        else
        {
            curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']??'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/79.0.3945.88 Safari/537.36 Lamson');
        }

        foreach($option as $key => $val)
        {
            curl_setopt($ch, $key, $val);
        }

        $response = curl_exec($ch);//执行并接收返回信息

        if(curl_errno($ch))
        {
            //出错则抛出异常
            throw new OAuthException(curl_error($ch), curl_errno($ch));
        }

        if(! empty($option[CURLOPT_HEADER]))
        {
            // 获得响应结果里的：头大小
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            // 根据头大小去获取头信息内容
            $_EVN['CURL_HEADER'] = substr($response, 0, $header_size);
            $response = substr($response, $header_size);
        }

        curl_close($ch); //关闭curl链接
        return $response;
    }
}
