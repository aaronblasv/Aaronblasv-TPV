<?php

use App\User\Infrastructure\Entrypoint\Http\PostController;
use Illuminate\Support\Facades\Route;
use App\Tax\Infrastructure\Entrypoint\Http\GetAllTaxesController;
use App\Tax\Infrastructure\Entrypoint\Http\CreateTaxController;
use App\Tax\Infrastructure\Entrypoint\Http\UpdateTaxController;
use App\Tax\Infrastructure\Entrypoint\Http\DeleteTaxController;
use App\Family\Infrastructure\Entrypoint\Http\GetAllFamiliesController;
use App\Family\Infrastructure\Entrypoint\Http\CreateFamilyController;
use App\Family\Infrastructure\Entrypoint\Http\UpdateFamilyController;
use App\Family\Infrastructure\Entrypoint\Http\DeleteFamilyController;

Route::post('/users', PostController::class);
Route::get('/taxes', GetAllTaxesController::class);
Route::post('/taxes', CreateTaxController::class);
Route::put('/taxes/{uuid}', UpdateTaxController::class);
Route::delete('/taxes/{uuid}', DeleteTaxController::class);
Route::get('/families', GetAllFamiliesController::class);
Route::post('/families', CreateFamilyController::class);
Route::put('/families/{uuid}', UpdateFamilyController::class);
Route::delete('/families/{uuid}', DeleteFamilyController::class);