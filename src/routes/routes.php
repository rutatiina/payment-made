<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('payments-made')->group(function () {

        Route::post('credit-accounts', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController@creditAccounts')->name('payments-made.credit-accounts');
        Route::post('bills', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController@bills')->name('payments-made.bills');

        Route::post('export-to-excel', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController@approve');
        //Route::post('contact-estimates', 'Rutatiina\PaymentMade\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController@copy');

        Route::post('credit-accounts', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController@creditAccounts');
        Route::post('bills', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController@bills');

    });

    Route::resource('payments-made/accounts', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeAccountController');
    Route::resource('payments-made/settings', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeSettingsController');
    Route::resource('payments-made', 'Rutatiina\PaymentMade\Http\Controllers\PaymentMadeController');

});
