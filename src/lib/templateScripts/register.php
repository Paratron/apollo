<?php

function process(){
    global $app, $conf;

    $data = \Kiss\Utils::array_clean($_POST, array(
       'firstName' => 'string|trim',
       'lastName' => 'string|trim',
       'email>mail' => 'mail',
       'password' => 'string',
       'repeatPassword' => 'string'
    ));

    if(!$data['mail']){
        $app->flash('registerError', 'invalidMail');
        $app->redirect('/register');
        return;
    }

    if(\Apollo\User::exists($data['mail'])){
        $app->flash('registerError', 'mailInUse');
        $app->redirect('/register');
                return;
    }

    if($data['password'] !== $data['repeatPassword']){
        $app->flash('registerError', 'noPasswordMatch');
                $app->redirect('/register');
                        return;
    }

    $u = new \Apollo\User();

    $u->set($data);

    $u->save();

    apolloTrigger('userRegisterMail', $u);

    if(isset($conf['requireEmailConfirmation']) && $conf['requireEmailConfirmation']){
        $u->sendActivationMail();
        $app->redirect('/preActivation/' . $u->getIdHash());
    } else {
        $u->setMailAddressConfirmed();
        $app->redirect('/afterActivation/' . $u->getIdHash(TRUE));
    }
}