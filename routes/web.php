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
            'prefix' => 'notification'
    
        ], function ($router) {

            Route::get('/index', 'NotificationController@index');
            // Route::get('/detail/{notification_id}', 'NotificationController@detail');
            // Route::put('/update/{notification_id}', 'NotificationController@update');
            // Route::post('/create', 'NotificationController@create');   
            // Route::delete('/destroy/{notification_id}', 'NotificationController@destroy');
    
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
 


        $router->group([
            'prefix' => 'system-code'
    
        ], function ($router) {

            Route::get('/get-all-by-system-code', 'SystemCodeController@getDataBySystemCode');
            Route::get('/index', 'SystemCodeController@index');
            Route::get('/detail/{system_code_id}', 'SystemCodeController@detail');
            Route::put('/update/{system_code_id}', 'SystemCodeController@update');
            Route::delete('/destroy/{system_code_id}', 'SystemCodeController@destroy');
            Route::post('/create', 'SystemCodeController@create');   
            Route::get('/download-template', 'SystemCodeController@downloadTemplate');
            Route::post('/upload', 'SystemCodeController@uploadData');   
    
        });
 


        $router->group([
            'prefix' => 'budget-year'
    
        ], function ($router) {

            Route::get('/index', 'BudgetAccountYearController@index');
            Route::get('/detail/{budget_year_id}', 'BudgetAccountYearController@detail');
            Route::get('/download-data-detail/{budget_year_id}', 'BudgetAccountYearController@downloadDataDetail');
            Route::put('/update/{budget_year_id}', 'BudgetAccountYearController@update');
            Route::post('/create', 'BudgetAccountYearController@create');  
    
        });
 


        $router->group([
            'prefix' => 'budget-header'
    
        ], function ($router) {

            Route::get('/index/{budget_year_id}', 'BudgetAccountHeaderController@index');
            Route::get('/detail/{budget_account_header_id}', 'BudgetAccountHeaderController@detail');
            Route::get('/download-data-detail/{budget_account_header_id}', 'BudgetAccountHeaderController@downloadDataDetail');
            Route::put('/update/{budget_account_header_id}', 'BudgetAccountHeaderController@update');
            Route::delete('/destroy/{budget_account_header_id}', 'BudgetAccountHeaderController@destroy');
            Route::post('/create/{budget_year_id}', 'BudgetAccountHeaderController@create');  
            Route::get('/download-template', 'BudgetAccountHeaderController@downloadTemplate');
            Route::post('/upload/{budget_year_id}', 'BudgetAccountHeaderController@uploadData');   
    
        });
 


        $router->group([
            'prefix' => 'budget-details'
    
        ], function ($router) {

            Route::get('/index/{budget_account_header_id}', 'BudgetAccountDetailsController@index');
            Route::get('/detail/{budget_account_detail_id}', 'BudgetAccountDetailsController@detail');
            Route::post('/create/{budget_account_header_id}', 'BudgetAccountDetailsController@create');  
            Route::put('/update/{budget_account_detail_id}', 'BudgetAccountDetailsController@update');
            Route::delete('/destroy/{budget_account_detail_id}', 'BudgetAccountDetailsController@destroy');
            Route::get('/download-template', 'BudgetAccountDetailsController@downloadTemplate');
            Route::post('/upload/{budget_account_header_id}', 'BudgetAccountDetailsController@uploadData');   
    
        });


        
    });


});
