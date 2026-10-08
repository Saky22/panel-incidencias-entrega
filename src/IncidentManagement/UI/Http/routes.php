<?php

use Illuminate\Support\Facades\Route;
use OptimaRetail\IncidentManagement\UI\Http\Controller\HelpController;
use OptimaRetail\IncidentManagement\UI\Http\Controller\IncidentController;
use OptimaRetail\IncidentManagement\UI\Http\Controller\OperatorController;

// Ayuda: documentación del repositorio legible desde la app.
Route::get('/ayuda', [HelpController::class, 'index'])->name('ayuda');

// Rutas del bounded context; las carga IncidentManagementServiceProvider con el grupo «web».
Route::controller(IncidentController::class)->prefix('incidents')->name('incidents.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/{incident}/history', 'history')->whereUuid('incident')->name('history');
    Route::patch('/{incident}/status', 'changeStatus')->whereUuid('incident')->name('status');
    Route::post('/{incident}/comments', 'storeComment')->whereUuid('incident')->name('comments.store');
});

Route::controller(OperatorController::class)->prefix('operators')->name('operators.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::patch('/{operator}/deactivate', 'deactivate')->whereUuid('operator')->name('deactivate');
});
