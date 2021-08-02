<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */

namespace OAuth\Gitee;

use OAuth\BaseOAuth;
use OAuth\OAuthException;

class OAuth extends BaseOAuth
{
    const API_DOMAIN = 'https://gitee.com';

    /** @var Config */
    protected $config;

    public function getAuthUrl()
    {
        $params = [
            'client_id' => $this->config->getClientId(),
            'redirect_uri' => $this->config->getRedirectUri(),
            'state' => $this->config->getState(),
            'response_type' => $this->config->getResponseType(),
        ];
        return $this->getUrl(self::API_DOMAIN . '/oauth/authorize', $params);
    }

    public function getUserInfo(string $accessToken)
    {
        $url = self::API_DOMAIN . '/api/v5/user?access_token=' . $accessToken;
        $body = $this->curl($url);

        if (!$body) throw new OAuthException('获取用户信息失败！');
        $result = \json_decode($body, true);

        if (!isset($result['id'])) {
            throw new OAuthException($result['message']);
        }

        return $result;
    }

    protected function __getAccessToken($state = null, $code = null)
    {
        $url = self::API_DOMAIN . '/oauth/token';
        $body = $this->curl($url, [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'client_id' => $this->config->getClientId(),
            'redirect_uri' => $this->config->getRedirectUri(),
            'client_secret' => $this->config->getClientSecret(),
        ]);

        if (!$body) throw new OAuthException('获取AccessToken失败！');

        $result = \json_decode($body, true);
        $this->accessTokenResult = $result;

        if (isset($result['error'])) {
            throw new OAuthException($result['error_description']);
        }

        return $result['access_token'];
    }

    public function validateAccessToken(string $accessToken)
    {
        try {
            $this->getUserInfo($accessToken);
            return true;
        } catch (OAuthException $exception) {
            return false;
        }
    }

    public function refreshToken(string $refreshToken = null)
    {
        return false;
    }
}
