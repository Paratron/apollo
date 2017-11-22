<?php

registerTriggerProcessor(function($name, $data){

});

/**
 * Generic trigger processor for apollo.
 * Feel free to remove this entirely, but it will
 * strip away things like transactional email processing.
 * @param {string} $name
 * @param {string} $data
 * @return bool
 */
function processTriggers($name, $data){
    switch($name){
        case 'userRegister':
            // TODO: push to Slack
            break;
    }
    return TRUE;
}