<?php

function process($idHash){
    global $app;

    $user = \Apollo\User::getByIdHash($idHash);

    if(!$user){
        //$app->redirect('/');
    }

    return array(
        'user' => $user->getJSON('browser')
    );
}