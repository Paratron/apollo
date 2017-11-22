<?php

$currentUser = NULL;

if (isset($_SESSION['apolloUser']) && $_SESSION['apolloUser']) {
    $currentUser = \Apollo\User::getBySession();
}

/**
 * The auto-router automatically creates GET and POST routes that can be defined in the settings.ini
 */
$globalTemplateData = array(
    '_user' => $currentUser ? $currentUser->getJSON('browser') : NULL,
    '_auth' => array(
        'mail' => $conf['system']['allowEmailLogin'],
        'google' => FALSE,
        'facebook' => FALSE,
        'github' => FALSE
    )
);

if (isset($conf['generic'])) {
    $globalTemplateData['_generic'] = $conf['generic'];
}


$setOAuthEndpoint = FALSE;

if (
    isset($conf['oAuthGoogle'])
    || isset($conf['oAuthFacebook'])
    || isset($conf['oAuthGithub'])
) {
    $setOAuthEndpoint = TRUE;
    $globalTemplateData['_auth']['google'] = isset($conf['oAuthGoogle']);
    $globalTemplateData['_auth']['facebook'] = isset($conf['oAuthFacebook']);
    $globalTemplateData['_auth']['github'] = isset($conf['oAuthGithub']);

    $app->get('/login/:provider', function ($provider) use ($app, $conf) {
        $confKey = 'oAuth' . strtoupper(substr($provider, 0, 1)) . strtolower(substr($provider, 1));
        if (!isset($conf[$confKey])) {
            die('Invalid request');
        }

        $redirectURI = $conf['system']['baseURL'] . 'processOAuth';
        $clientId = $conf[$confKey]['clientId'];
        $stateKey = md5(uniqid());
        $_SESSION['oAuth_state'] = $stateKey;
        $state = $provider . '-' . $stateKey;

        $oAuthUrls = array(
            'google' => 'https://accounts.google.com/o/oauth2/v2/auth',
            'facebook' => 'https://www.facebook.com/v2.11/dialog/oauth',
            'github' => 'https://github.com/login/oauth/authorize'
        );

        $scopes = array(
            'google' => 'openid profile',
            'facebook' => 'public_profile',
            'github' => ''
        );

        $app->redirect($oAuthUrls[$provider] . '?state=' . $state . '&client_id=' . $clientId . '&response_type=code&scope=' . $scopes[$provider] . '&redirect_uri=' . $redirectURI);
    });
}

if ($setOAuthEndpoint) {
    $app->get('/processOAuth', function () use ($app, $conf) {
        if (isset($_GET['error_code'])) {
            // TODO: catch oAuth errors!
            $app->redirect('/');
        }

        $state = explode('-', $_GET['state']);

        if (!isset($_SESSION['oAuth_state']) || $_SESSION['oAuth_state'] !== $state[1]) {
            throw new ErrorException('State not set or invalid');
        }

        if (isset($_GET['error'])) {
            $app->redirect($conf['system']['baseURL']);
            return;
        }

        switch ($state[0]) {
            case 'google':
                if (!isset($conf['oAuthGoogle'])) {
                    throw new ErrorException('Google oAuth provider not configured');
                }

                $c = new \Kiss\CurlInterface();
                $result = $c->post('https://www.googleapis.com/oauth2/v4/token', array(
                    'code' => $_GET['code'],
                    'client_id' => $conf['oAuthGoogle']['clientId'],
                    'client_secret' => $conf['oAuthGoogle']['clientKey'],
                    'redirect_uri' => $conf['system']['baseURL'] . 'processOAuth',
                    'grant_type' => 'authorization_code'
                ));

                if (!isset($result['access_token'])) {
                    throw new ErrorException('An error happened while requesting your access token');
                }

                $decoded = explode('.', $result['id_token']);
                $decoded = base64_decode($decoded[1]);
                $decoded = json_decode($decoded, TRUE);

                $googleUserId = $decoded['sub'];

                $user = \Apollo\User::getByGoogle($googleUserId);
                if (!$user) {
                    $user = new \Apollo\User();

                    $c->reset();
                    $c->auth('Bearer', $result['access_token']);
                    $userdata = $c->get('https://www.googleapis.com/oauth2/v2/userinfo');

                    $user->oAuthGoogle = $googleUserId;
                    $user->firstName = $userdata['given_name'];
                    $user->lastName = $userdata['family_name'];

                    $user->save();
                    apolloTrigger('userRegisterOAuth', $user);
                }

                $_SESSION['apolloUser'] = $user->id;

                apolloTrigger('userLogin', $user);
                apolloTrigger('userLoginOAuth', $user);

                $app->redirect($conf['system']['baseURL']);
                return;
                break;
            case 'facebook':
                if (!isset($conf['oAuthFacebook'])) {
                    throw new ErrorException('Facebook oAuth provider not configured');
                }

                $c = new \Kiss\CurlInterface();
                $result = $c->post('https://graph.facebook.com/v2.11/oauth/access_token', array(
                    'code' => $_GET['code'],
                    'client_id' => $conf['oAuthFacebook']['clientId'],
                    'client_secret' => $conf['oAuthFacebook']['clientKey'],
                    'redirect_uri' => $conf['system']['baseURL'] . 'processOAuth'
                ));

                if (!isset($result['access_token'])) {
                    throw new ErrorException('An error happened while requesting your access token');
                }

                $result = $c->get('https://graph.facebook.com/v2.11/me?fields=id,first_name,last_name&access_token=' . $result['access_token']);

                if (!isset($result['id'])) {
                    throw new ErrorException('An error happened while accessing your facebook data with your token.');
                }

                $user = \Apollo\User::getByFacebook($result['id']);
                if (!$user) {
                    $user = new \Apollo\User();

                    $user->oAuthFacebook = $result['id'];
                    $user->firstName = $result['first_name'];
                    $user->lastName = $result['last_name'];

                    $user->save();
                }


                $_SESSION['apolloUser'] = $user->id;

                $app->redirect($conf['system']['baseURL']);
                return;
                break;
            case 'github':
                if (!isset($conf['oAuthGithub'])) {
                    throw new ErrorException('GitHub oAuth provider not configured');
                }

                $c = new \Kiss\CurlInterface();
                $result = $c->post('https://github.com/login/oauth/access_token', array(
                    'code' => $_GET['code'],
                    'client_id' => $conf['oAuthGithub']['clientId'],
                    'client_secret' => $conf['oAuthGithub']['clientKey'],
                    'redirect_uri' => $conf['system']['baseURL'] . 'processOAuth',
                    'state' => implode('-', $state)
                ));

                $kv = array();
                $result = explode('&', $result);
                foreach($result as $k => $v){
                    $v = explode('=', $v);
                    $kv[$v[0]] = $v[1];
                }
                $result = $kv;

                if (!isset($result['access_token'])) {
                    throw new ErrorException('An error happened while requesting your access token');
                }

                $c->reset();
                $c->header('User-Agent', 'Apollo');
                $result = $c->get('https://api.github.com/user?access_token=' . $result['access_token']);

                $user = \Apollo\User::getByGithub($result['id']);
                if (!$user) {
                    $user = new \Apollo\User();

                    $user->oAuthGithub = $result['id'];

                    $names = $result['name'] ? explode(' ', $result['name']) : array($result['login']);

                    switch(count($names)){
                        case 1:
                            $user->lastName = $names[0];
                            break;
                        case 2:
                            $user->firstName = $names[0];
                            $user->lastName = $names[1];
                            break;
                        default:
                            $user->firstName = array_shift($names);
                            $user->lastName = implode(' ', $names);
                    }

                    $user->save();
                }


                $_SESSION['apolloUser'] = $user->id;

                $app->redirect($conf['system']['baseURL']);
                return;
                break;
            default:
                throw new ErrorException('Invalid oAuth provider');
        }
    });
}


if (isset($conf['get'])) {
    foreach ($conf['get'] as $fragment => $template) {

        $template = explode('>', $template);

        $app->get($fragment, function () use ($template, $app, $globalTemplateData) {
            $args = func_get_args();


            if (isset($template[1])) {
                require 'lib/templateScripts/' . trim($template[0]);

                if (!function_exists('process')) {
                    throw new ErrorException('There is no process() method in `' . $template[0] . '`');
                }

                if (count($args)) {
                    $data = call_user_func_array('process', $args);
                } else {
                    $data = process();
                }

                if (!is_array($data)) {
                    throw new ErrorException('The process() method in `' . $template[0] . '` must return an array.');
                }

                $app->render(trim($template[1]), array_merge($data, $globalTemplateData));
                return;
            }

            $app->render($template[0], $globalTemplateData);
        });
    }
}

// TODO: DRY this
if (isset($conf['post'])) {
    foreach ($conf['post'] as $fragment => $template) {
        $template = explode('>', $template);

        $app->post($fragment, function () use ($template, $app, $globalTemplateData) {

            if (isset($template[1])) {
                require 'lib/templateScripts/' . trim($template[0]);

                if (!function_exists('process')) {
                    throw new ErrorException('There is no process() method in `' . $template[0] . '`');
                }

                $data = process();

                if (!is_array($data)) {
                    throw new ErrorException('The templateData() method in `' . $template[0] . '` must return an array.');
                }

                $app->render($template[1], array_merge($data, $globalTemplateData));
                return;
            }

            $tpl = trim($template[0]);

            if (substr($tpl, -3) === 'php') {
                require 'lib/templateScripts/' . trim($tpl);

                if (!function_exists('process')) {
                    throw new ErrorException('There is no process() method in `' . $template[0] . '`');
                }

                process();

                return;
            }

            $app->render($tpl, $globalTemplateData);
        });
    }
}