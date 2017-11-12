<?php

$db = NULL;
$cache = NULL;
$mailgun = NULL;

/**
 * Returns a new instance of the database.
 * @return \Kiss\MySQLi|null
 */
function requireDatabase()
{
    global $db, $conf;

    if ($db) {
        return $db;
    }

    $db = new \Kiss\MySQLi($conf['database']);
    $db->throwErrors = TRUE;

    return $db;
}

function requireMailgun(){
    global $mailgun, $conf;

    if(!isset($conf['mailgun']) || !isset($conf['mailgun']['apiKey'])){
        throw new ErrorException('No Mailgun api key defined.');
    }

    if($mailgun){
        return $mailgun;
    }

    $mailgun = new \Kiss\Mailgun($conf['mailgun']['apiKey'], $conf['mailgun']['apiDomain']);

    return $mailgun;
}