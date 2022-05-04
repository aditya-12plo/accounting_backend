<?php

Route::group([
    'middleware' => 'lang.auth'
], function ($router) {


    $router->get('/',['as' => 'index','uses' => 'IndexController@index']);
    $router->get('/get-companys',['as' => 'companyGetAll','uses' => 'CompanyController@getAll']);

    $router->post('/auth/login',['as' => 'index','uses' => 'AuthController@login']);
    $router->post('/auth/reset-password',['as' => 'userResetPassword','uses' => 'AuthController@userResetPassword']);
    $router->post('/auth/change-password',['as' => 'userChangePassword','uses' => 'AuthController@userChangePassword']);

    
    $router->group([
        'prefix' => 'xxxadminxxx'

    ], function ($router) {
    
        $router->get('version', function () use ($router) {
            return $router->app->version();
        });
    
        $router->get('coba',['as' => 'xxadminxxCoba','uses' => 'ExampleController@index']);


    });

    Route::group([
        'middleware' => 'jwt.auth'
    ], function ($router) {

        Route::get('/dashboard', 'AuthController@index');
        Route::get('/profile', 'AuthController@profile');

        Route::post('/user/change-password', 'UserController@changePassword');
        Route::get('/user/index', 'UserController@index');
        Route::put('/user/update-status/{user_id}', 'UserController@updateStatus');
        Route::get('/user/detail/{user_id}', 'UserController@detail');
        Route::put('/user/update/{user_id}', 'UserController@update');
        Route::post('/user/create', 'UserController@create');


        Route::get('/division/all', 'DivisionController@getAllData');
        
        Route::get('/company/all', 'CompanyController@getAllData');
    });


});
