<?php
Route::namespace('Invoice')->group(function () {

    Route::get('{business_id}/{b_invoice}', 'InvoiceController@show')->name('invoice.hosted.show');
    Route::get('{business_id}/download/{b_invoice}', 'InvoiceController@download')->name('invoice.download');
});
