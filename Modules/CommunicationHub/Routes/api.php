<?php

use Illuminate\Support\Facades\Route;
use Modules\CommunicationHub\Http\Controllers\CommunicationHubApiGatewayController;

Route::prefix('communication-hub')->as('api.communicationhub.')->group(function () {
    Route::get('health', [CommunicationHubApiGatewayController::class, 'health'])->name('health');

    Route::prefix('gateway')->as('gateway.')->group(function () {
        Route::post('send', [CommunicationHubApiGatewayController::class, 'send'])->name('send');
        Route::post('send/sms', [CommunicationHubApiGatewayController::class, 'sendSms'])->name('send.sms');
        Route::post('send/email', [CommunicationHubApiGatewayController::class, 'sendEmail'])->name('send.email');
        Route::post('send/whatsapp', [CommunicationHubApiGatewayController::class, 'sendWhatsapp'])->name('send.whatsapp');
        Route::post('send/push', [CommunicationHubApiGatewayController::class, 'sendPush'])->name('send.push');
        Route::post('estimate-cost', [CommunicationHubApiGatewayController::class, 'estimateCost'])->name('estimate_cost');
        Route::get('delivery-status/{messageId}', [CommunicationHubApiGatewayController::class, 'deliveryStatus'])->name('delivery_status');
        Route::post('otp/generate', [CommunicationHubApiGatewayController::class, 'generateOtp'])->name('otp.generate');
        Route::post('otp/verify', [CommunicationHubApiGatewayController::class, 'verifyOtp'])->name('otp.verify');
    });
});
