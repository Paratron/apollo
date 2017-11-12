<?php

$currentUser = NULL;

if(isset($_SESSION['apolloUser']) && $_SESSION['apolloUser']){
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

if (isset($conf['oAuthGoogle'])) {
    $setOAuthEndpoint = TRUE;
    $globalTemplateData['_auth']['google'] = TRUE;

    $app->get('/login/google', function () use ($app, $conf) {
        $redirectURI = $conf['system']['baseURL'] . 'processOAuth';
        $clientId = $conf['oAuthGoogle']['clientId'];
        $stateKey = md5(uniqid());
        $_SESSION['oAuth_state'] = $stateKey;
        $state = 'google-' . $stateKey;
        $app->redirect('https://accounts.google.com/o/oauth2/v2/auth?state=' . $state . '&client_id=' . $clientId . '&response_type=code&scope=openid profile&redirect_uri=' . $redirectURI);
    });
}

if ($setOAuthEndpoint) {
    $app->get('/processOAuth', function () use ($app, $conf) {
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

                    $user->oAuthGoogle = $googleUserId;
                    $user->save();
                }


                $_SESSION['apolloUser'] = $user->id;

                $app->redirect($conf['system']['baseURL']);
                return;
                break;
            case 'facebook':

                break;
            case 'github':

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