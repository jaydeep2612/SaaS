<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WaiterController;
use App\Http\Controllers\Api\CustomerController;

// Public routes (No login required for customers)
use App\Http\Controllers\Api\TableStatusController;

Route::get('/table/{id}/status', [TableStatusController::class, 'checkStatus']);
Route::post('/table/{id}/occupy', [TableStatusController::class, 'occupy']);
Route::post('/table/{id}/free', [TableStatusController::class, 'free']);
Route::get('/customer/menu/{restaurantId}', [CustomerController::class, 'getMenu']);
Route::post('/customer/order', [CustomerController::class, 'placeOrder']);
Route::post('/waiter/login', [WaiterController::class, 'login']);
Route::get('/waiter/orders', [WaiterController::class, 'getReadyOrders']);
Route::post('/waiter/order/{id}/serve', [WaiterController::class, 'markServed']);

Route::get('/user', function (Request $request) {    return $request->user(); })->middleware('auth:sanctum');
