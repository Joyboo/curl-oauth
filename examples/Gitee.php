<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


class Gitee
{
    public function index()
    {
        $config = new \OAuth\Gitee\Config();
        $config->setState('easyswoole');
        $config->setClientId('clientid');
        $config->setRedirectUri('redirect_uri');
        $oauth = new \OAuth\Gitee\OAuth($config);
        $this->response()->redirect($oauth->getAuthUrl());
    }

    public function callback()
    {
        $params = $this->request()->getQueryParams();

        $config = new \OAuth\Gitee\Config();
        $config->setClientId('client_id');
        $config->setClientSecret('secret');
        $config->setRedirectUri('redirect_uri');

        $oauth = new \OAuth\Gitee\OAuth($config);
        $accessToken = $oauth->getAccessToken('easyswoole', $params['state'], $params['code']);
        $userInfo = $oauth->getUserInfo($accessToken);
        var_dump($userInfo);

        if (!$oauth->validateAccessToken($accessToken)) echo 'access_token 验证失败！' . PHP_EOL;
        var_dump($oauth->getAccessTokenResult());
    }
}
