<?php

$router->get('/',['as' => 'index','uses' => 'IndexController@index']);

$router->get('/root', function () use ($router) {
    return $router->app->version();
});


$router->group([
    'prefix' => 'xxadminxx'

], function ($router) {
 
    $router->get('version', function () use ($router) {
        return $router->app->version();
    });
 
    $router->get('coba',['as' => 'xxadminxxCoba','uses' => 'ExampleController@index']);


});
