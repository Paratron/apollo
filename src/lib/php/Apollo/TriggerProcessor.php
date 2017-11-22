<?php

$_triggerProcessors = array();


function registerTriggerProcessor($triggerProcessor)
{
    global $_triggerProcessors;
    $_triggerProcessors[] = $triggerProcessor;
}

/**
 * Triggers an event. It will be forwarded to all registered trigger processors.
 * @param {string} $name
 * @param {string} $data
 */
function apolloTrigger($name, $data)
{
    global $_triggerProcessors;
    $reducerCount = count($_triggerProcessors);

    for ($i = 0; $i < $reducerCount; $i++) {
        $result = $_triggerProcessors[$i]($name, $data);
        if (!$result) {
            break;
        }
    }
}

$files = scandir('./lib/triggerProcessors');
foreach($files as $file){
    if(substr($file,0, -4) === '.php'){
        include 'lib/php/triggerProcessors/' . $file;
    }
}