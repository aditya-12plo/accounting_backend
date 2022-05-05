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

        $router->group([
            'prefix' => 'user'
    
        ], function ($router) {

            Route::post('/change-password', 'UserController@changePassword');
            Route::get('/index', 'UserController@index');
            Route::put('/update-status/{user_id}', 'UserController@updateStatus');
            Route::get('/detail/{user_id}', 'UserController@detail');
            Route::put('/update/{user_id}', 'UserController@update');
            Route::post('/create', 'UserController@create');   
    
        });

        $router->group([
            'prefix' => 'company'
    
        ], function ($router) {

            Route::get('/all', 'CompanyController@getAllData');
            Route::get('/index', 'CompanyController@index');
            Route::get('/detail/{company_id}', 'CompanyController@detail');
            Route::put('/update/{company_id}', 'CompanyController@update');
            Route::post('/create', 'CompanyController@create');   
    
        });


        Route::get('/division/all', 'DivisionController@getAllData');
        
    });


});
