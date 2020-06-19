<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


namespace EasySwoole\OAuth\Weibo;


use EasySwoole\HttpClient\HttpClient;
use EasySwoole\OAuth\BaseOAuth;
use EasySwoole\OAuth\OAuthException;

class OAuth extends BaseOAuth
{

    const API_DOMAIN = 'https://api.weibo.com';

    const API_MOBILE_DOMAIN = 'https://open.weibo.cn';

    /** @var Config */
    protected $config;

    protected $uid;

    public function getAuthUrl()
    {
        $params = http_build_query([
            'client_id' => $this->config->getClientId(),
            'redirect_uri' => $this->config->getRedirectUri(),
            'display' => $this->config->getDisplay(),
            'scope' => $this->config->getScope(),
            'state' => $this->config->getState(),
            'forcelogin' => $this->config->getForceLogin(),
            'language' => $this->config->getLanguage()
        ]);
        if ($this->config->getDisplay() == 'mobile') {
            return self::API_MOBILE_DOMAIN . '/oauth2/authorize?' . $params;
        }
        return self::API_DOMAIN . '/oauth2/authorize?' . $params;
    }

    protected function __getAccessToken($state = null, $code = null)
    {
        $client = (new HttpClient(self::API_DOMAIN . '/oauth2/access_token'))
            ->post([
                'client_id' => $this->config->getClientId(),
                'redirect_uri' => $this->config->getRedirectUri(),
                'client_secret' => $this->config->getClientSecret(),
                'grant_type' => $this->config->getGrantType(),
                'code' => $code,
            ]);


        $body = $client->getBody();

        if (!$body) throw new OAuthException('获取AccessToken失败！');

        $result = \json_decode($body, true);

        if (isset($result['error'])) {
            throw new OAuthException($result['error']);
        }

        $this->uid = $result['uid'];
        return $result['access_token'];
    }

    public function getUserInfo(string $accessToken)
    {
        $client = (new HttpClient(self::API_DOMAIN . '/2/users/show.json'))
            ->setQuery([
                'access_token' => $accessToken,
                'uid' => $this->uid
            ])
            ->get();

        $body = $client->getBody();

        if (!$body) throw new OAuthException('获取用户信息失败！');

        $result = \json_decode($body, true);

        if (isset($result['error_code'])) {
            throw new OAuthException($result['error'], $result['error_code']);
        }

        return $result;
    }
}