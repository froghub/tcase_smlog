<?php

use App\Http\Controllers\NotificationController;

Route::group(['prefix' => 'notifications'], function () {
    Route::post('send', [NotificationController::class, 'send']);
    Route::get('get/{id}', [NotificationController::class, 'recipientHistory']);
});
