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
            Route::get('/company', 'UserController@userCompany');
            Route::put('/update-status/{user_id}', 'UserController@updateStatus');
            Route::get('/detail/{user_id}', 'UserController@detail');
            Route::post('/update/{user_id}', 'UserController@update');
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


        $router->group([
            'prefix' => 'division'
    
        ], function ($router) {

            Route::get('/all', 'DivisionController@getAllData');
            Route::get('/index', 'DivisionController@index');
            Route::get('/detail/{division_id}', 'DivisionController@detail');
            Route::put('/update/{division_id}', 'DivisionController@update');
            Route::post('/create', 'DivisionController@create');   
    
        });


        $router->group([
            'prefix' => 'vendor'
    
        ], function ($router) {

            Route::get('/all', 'VendorController@getAllData');
            Route::get('/index', 'VendorController@index');
            Route::get('/detail/{vendor_id}', 'VendorController@detail');
            Route::put('/update/{vendor_id}', 'VendorController@update');
            Route::post('/create', 'VendorController@create');   
            Route::get('/download-template', 'VendorController@downloadTemplate');
            Route::post('/upload', 'VendorController@uploadData');   
    
        });
 
        
    });


});
