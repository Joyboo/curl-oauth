<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


namespace OAuth\WeiXin;


use OAuth\BaseOAuth;
use OAuth\OAuthException;

class OAuth extends BaseOAuth
{
    const API_DOMAIN = 'https://api.weixin.qq.com';

    const OPEN_DOMAIN = 'https://open.weixin.qq.com';

    /** @var Config */
    protected $config;

    protected $openId;

    public function getAuthUrl()
    {
        $params = [
            'appid' => $this->config->getAppId(),
            'redirect_uri' => $this->config->getRedirectUri(),
            'response_type' => $this->config->getResponseType(),
            'scope' => $this->config->getScope(),
            'state' => $this->config->getState(),
        ];
        return $this->getUrl(self::OPEN_DOMAIN . '/connect/qrconnect', $params) . '#wechat_redirect';
    }

    protected function __getAccessToken($state = null, $code = null)
    {
        $body = $this->curl(self::API_DOMAIN . '/sns/oauth2/access_token?' . http_build_query([
                'appid' => $this->config->getAppId(),
                'secret' => $this->config->getSecret(),
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]));

        if (!$body) throw new OAuthException('获取AccessToken失败！');

        $result = \json_decode($body, true);
        $this->accessTokenResult = $result;

        if (isset($result['errcode']) && 0 != $result['errcode']) {
            throw new OAuthException($result['errmsg'], $result['errcode']);
        }

        $this->openId = $result['openid'];

        switch ($this->config->getOpenIdMode()) {
            case $this->config::OPEN_ID:
                $this->openId = $result['openid'];
                break;
            case $this->config::UNION_ID:
                $this->openId = $result['unionid'];
                break;
            default:
                throw new OAuthException('openid mode 设置有误！');
        }

        return $result['access_token'];
    }

    public function getUserInfo(string $accessToken)
    {
        $body = $this->curl(self::API_DOMAIN . '/sns/userinfo?' . http_build_query([
                'access_token' => $accessToken,
                'openid' => $this->openId,
                'lang' => $this->config->getLang(),
            ]));

        if (!$body) throw new OAuthException('获取用户信息失败！');

        $result = \json_decode($body, true);

        if (isset($result['errcode']) && 0 != $result['errcode']) {
            throw new OAuthException($result['errmsg'], $result['errcode']);
        }

        return $result;
    }

    public function refreshToken(string $refreshToken = null)
    {
        $params = [
            'appid' => $this->config->getAppId(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken
        ];

        $body = $this->curl(self::API_DOMAIN . '/sns/oauth2/refresh_token?' . http_build_query($params));

        if (!$body) return false;

        $result = \json_decode($body, true);
        $this->refreshTokenResult = $result;

        return !isset($result['errcode']);
    }

    public function validateAccessToken(string $accessToken)
    {
        $params = [
            'access_token' => $accessToken,
            'openid' => $this->openId
        ];

        $body = $this->curl(self::API_DOMAIN . '/sns/auth?' . http_build_query($params));

        if (!$body) return false;

        $result = \json_decode($body, true);
        return isset($result['errcode']) && 0 == $result['errcode'];
    }

    /**
     * @param mixed $openId
     */
    public function setOpenId($openId): void
    {
        $this->openId = $openId;
    }
}
