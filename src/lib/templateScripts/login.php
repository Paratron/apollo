<?php

function process()
{
    global $app;

    $data = \Kiss\Utils::array_clean($_POST, array(
        'identifier' => 'string',
        'password' => 'string'
    ));

    try {
        $user = new \Apollo\User($data['identifier']);
    } catch (ErrorException $e) {
        $app->flash('loginError', 'unknownUser');
        $app->redirect('/');
        return;
    }

    if (!$user->login($data['password'])) {
        $app->flash('loginError', 'wrongPassword');
        $app->redirect('/');
        return;
    }

    $user->enableSession();

    apolloTrigger('userLogin', $user);
    apolloTrigger('userLoginMail', $user);

    $app->redirect('/');
}