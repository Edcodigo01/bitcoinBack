<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group([
    'middleware' => ['api', 'cors'],
], function () {
    Route::get('prueba', 'front\UserController@prueba');
    Route::get('view-mail', 'front\UserController@viewMail');
    Route::get('prueba-mail', 'front\UserController@pruebaMail');
    // AUTH
    Route::post('recover-password-request', 'front\UserController@recover_password_request');
    Route::post('recover-password-verify', 'front\UserController@recover_password_verify');
    Route::post('recover-password', 'front\UserController@recover_password');
    Route::post('login', 'front\UserController@login');
    Route::post('register', 'front\UserController@register');
    Route::get('confirmar-correo/{email}/{token}', 'front\UserController@confirm_mail');
    Route::post('resend-email-confirm/{email}', 'front\UserController@resend_email_confirm');

    Route::post('plans', 'admin\PlanController@list');
    Route::post('get-reference', 'ReferallsController@getReference');

    Route::post('get-referall-details', 'ReferallsController@get_referall_details');

    Route::group([
        'middleware' => ['auth:api'],
    ], function () {
        Route::post('get-auth', 'front\UserController@getAuth');
        Route::post('get-balance', 'front\UserController@get_balance');
        Route::post('next-day', 'front\UserController@next_day');


        Route::post('refresh-token', 'front\UserController@refreshToken');
        Route::post('bank-accounts', 'admin\BankAccountController@list');

        Route::group([
            'middleware' => ['admin'], 'prefix' => 'admin'
        ], function () {
            Route::post('delete-img', 'Controller@delete_img');
            // plan admin
            Route::post('plans', 'admin\PlanController@listAdmin');
            Route::post('plan-store', 'admin\PlanController@store');
            Route::post('plan-update', 'admin\PlanController@update');
            Route::post('plan-delete/{id}', 'admin\PlanController@delete');
            Route::post('update-license', 'admin\PlanController@update_license');
            // bancos
            Route::post('bank-accounts', 'admin\BankAccountController@list');
            Route::post('bank-account/{id}', 'admin\BankAccountController@get');
            Route::post('bank-account-store', 'admin\BankAccountController@store');
            Route::post('bank-account-update', 'admin\BankAccountController@update');
            Route::post('bank-account-delete/{id}', 'admin\BankAccountController@delete');
            // Carteras
            Route::post('wallets', 'admin\WalletController@list');
            Route::post('wallet/{id}', 'admin\WalletController@get');
            Route::post('wallet-store', 'admin\WalletController@store');
            Route::post('wallet-update', 'admin\WalletController@update');
            Route::post('wallet-delete/{id}', 'admin\WalletController@delete');
            // USER PLAN ADMIN
            Route::post('list-user-plan', 'front\UserPlanController@list');
            Route::post('get-plan-user-admin', 'front\UserPlanController@getPlanAdmin');
            Route::post('activate-plan', 'front\UserPlanController@activatePlan');
            Route::post('reject-plan', 'front\UserPlanController@rejectPlan');
            // REQUEST WITHDRAWAL
            Route::post('list-withdrawal', 'admin\WithdrawalController@list');
            Route::post('upload-file-withdrawal', 'admin\WithdrawalController@upload_file');
            Route::post('images-pay-withdrawal', 'admin\WithdrawalController@getImages');
            Route::post('reject-withdrawal', 'admin\WithdrawalController@reject');
            Route::post('confirm-withdrawal', 'admin\WithdrawalController@confirmPay');
            Route::post('get-withdrawal', 'admin\WithdrawalController@get');
            // MONEDAS
            Route::post('list-coins', 'admin\CoinController@list_coins');
            Route::post('coins-store', 'admin\CoinController@store');
            Route::post('coins-update', 'admin\CoinController@update');
            Route::post('coins-delete', 'admin\CoinController@delete');
            // REFERIDOS
            Route::post('earnings-referralls', 'ReferallsController@earnings_referralls');
            Route::post('update-earnings-referralls', 'ReferallsController@update_earnings_referralls');

            Route::post('referidos', 'ReferallsController@referalls');
        });

        Route::group([
            'middleware' => ['cliente'],
        ], function () {
            //  USER PLAN FRONT
            // Route::post('balance', 'front\BalancePlanController@get');

            Route::post('my-plan', 'front\UserPlanController@my_plan');
            Route::post('activate-plan/{id}', 'front\UserPlanController@activate_plan');

            Route::post('upload-file-verification', 'front\UserController@upload_file_verification');
            Route::post('delete-image', 'front\UserController@delete_image');

            Route::post('update-data-personal', 'front\UserController@update_data_personal');
            Route::post('update-data-contact', 'front\UserController@update_data_contact');
            Route::post('get-accounts-payment', 'front\UserPlanController@get_accounts_payment');
            Route::post('get-plan-user', 'front\UserPlanController@get');
            Route::post('insert-amount-plan', 'front\UserPlanController@insertAmount');
            Route::post('insert-accounts-payment', 'front\UserPlanController@insertAccountsPayment');
            Route::post('images-pay-plan', 'front\UserPlanController@getImages');

            Route::post('upload-file-userplan', 'front\UserPlanController@upload_file');
            Route::post('delete-file-userplan', 'front\UserPlanController@delete_file');
            Route::post('insert-transaction-number', 'front\UserPlanController@insert_transaction_number');

            Route::post('request-activation-userplan', 'front\UserPlanController@request_activation');
            Route::post('plan-under-review', 'front\UserPlanController@plan_under_review');

            Route::post('available-for-withdrawal', 'front\UserController@available_for_withdrawal');
            Route::post('validate-data-withdrawal', 'front\UserController@validate_data_withdrawal');
            Route::post('generate-code-withdrawal', 'front\UserController@generate_code_withdrawal');
            Route::post('complete-request-withdrawal', 'front\UserController@complete_request_withdrawal');
            Route::post('list-withdrawal-user', 'front\UserController@list_withdrawal_user');
            Route::post('get-withdrawal-user', 'front\UserController@get_withdrawal_user');

            // REFERIDOS
            Route::post('referidos-usuario', 'ReferallsController@referalls_user');
            Route::post('earnings-referralls-front', 'ReferallsController@earnings_referralls');
            
            Route::post('change-alias-user', 'front\UserController@change_alias');
            Route::post('get-withdrawal', 'front\UserController@get_withdrawal');

            
            Route::group([
                'middleware' => ['plan'],
            ], function () {
            });
        });
    });
});
