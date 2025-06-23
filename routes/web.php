<?php

use App\Http\Controllers\DatabaseGeneratorController;

Route::get('/', [DatabaseGeneratorController::class, 'index'])->name('home');

Route::prefix('generator')->group(function () {
    
    Route::post('/upload', [DatabaseGeneratorController::class, 'upload'])->name('upload');
    Route::get('/preview', [DatabaseGeneratorController::class, 'preview'])->name('preview');
    Route::post('/generate', [DatabaseGeneratorController::class, 'generate'])->name('generate');
});



