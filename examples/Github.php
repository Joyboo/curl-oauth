<?php
/**
 * @author gaobinzhan <gaobinzhan@gmail.com>
 */


class Github
{
    public function index()
    {
        $config = new \OAuth\Github\Config();
        $config->setClientId('clientid');
        $config->setRedirectUri('redirect_uri');
        $config->setState('easyswoole');
        $oauth = new \OAuth\Github\OAuth($config);
        $this->response()->redirect($oauth->getAuthUrl());
    }

    public function callback()
    {
        $params = $this->request()->getQueryParams();
        $config = new \OAuth\Github\Config();
        $config->setClientId('clientid');
        $config->setClientSecret('secret');
        $config->setRedirectUri('redirect_uri');

        $oauth = new \OAuth\Github\OAuth($config);
        $accessToken = $oauth->getAccessToken('easyswoole', $params['state'], $params['code']);
        $userInfo = $oauth->getUserInfo($accessToken);
        var_dump($userInfo);

        if (!$oauth->validateAccessToken($accessToken)) echo 'access_token 验证失败！' . PHP_EOL;
    }
}
