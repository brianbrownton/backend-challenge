<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/


$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/test', ['uses' => 'EverlywellApi@test']);
$router->get('/listMembers', ['uses' => 'EverlywellApi@ListMembers']);
$router->get('/viewMember/{memberId}', ['uses' => 'EverlywellApi@ViewMember']);
$router->post('/addMember', ['uses' => 'EverlywellApi@AddMember']);
$router->post('/createFriendship/{mIdOne}/{mIdTwo}', ['uses' => 'EverlywellApi@CreateFriendship']);
$router->post('/search', ['uses' => 'EverlywellApi@Search']);