<?php
/**
 * Apollo Bootstrap
 * ================
 * This project bootstrap enables you to quickly create web service pages you can earn money with.
 *
 * @version 1.0
 */
session_start();
$conf = parse_ini_file('lib/config/settings.ini', TRUE);

require 'lib/php/Apollo/functions.php';

require 'lib/php/Slim/Slim.php';
\Slim\Slim::registerAutoloader();


$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig(),
    'debug' => $conf['system']['debug']
));

if (function_exists('header_remove')) {
    header_remove('X-Powered-By'); // PHP 5.3+
} else {
    @ini_set('expose_php', 'off');
}
$app->response()->headers->set('X-Powered-By', 'Magic Fairy Dust');
$app->response()->headers->set('Strict-Transport-Security', 'max-age=31536000');

$view = $app->view();
$view->setTemplatesDirectory('lib/templates/' . ($conf['system']['theme'] ? $conf['system']['theme'] . '/' : ''));
$view->parserOptions = array(
    'cache' => $conf['system']['debug'] ? '' : 'lib/php/cache/twig'
);
//$twig = $view->getInstance();

require 'lib/php/Apollo/AutoRouter.php';

$app->run();