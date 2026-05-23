<?php

declare(strict_types=1);

use App\Controllers\AdminAuthController;
use App\Controllers\AvailabilityController;
use App\Controllers\BookingAdminController;
use App\Controllers\ClientBookingController;
use App\Controllers\DashboardController;
use App\Controllers\GoogleController;

$router->add('GET', '/', [ClientBookingController::class, 'index']);
$router->add('GET', '/book', [ClientBookingController::class, 'index']);
$router->add('GET', '/book/slot/{id}', [ClientBookingController::class, 'showForm']);
$router->add('POST', '/book/confirm', [ClientBookingController::class, 'confirm']);
$router->add('POST', '/book/store', [ClientBookingController::class, 'store']);
$router->add('GET', '/book/complete', [ClientBookingController::class, 'complete']);

$router->add('GET', '/admin/login', [AdminAuthController::class, 'showLogin']);
$router->add('POST', '/admin/login', [AdminAuthController::class, 'login']);
$router->add('POST', '/admin/logout', [AdminAuthController::class, 'logout'], ['auth' => true]);

$router->add('GET', '/admin', [DashboardController::class, 'index'], ['auth' => true]);
$router->add('GET', '/admin/availability-slots', [AvailabilityController::class, 'index'], ['auth' => true]);
$router->add('GET', '/admin/availability-slots/create', [AvailabilityController::class, 'create'], ['auth' => true]);
$router->add('POST', '/admin/availability-slots', [AvailabilityController::class, 'store'], ['auth' => true]);
$router->add('POST', '/admin/availability-slots/{id}/toggle', [AvailabilityController::class, 'toggle'], ['auth' => true]);

$router->add('GET', '/admin/bookings', [BookingAdminController::class, 'index'], ['auth' => true]);
$router->add('GET', '/admin/bookings/{id}', [BookingAdminController::class, 'show'], ['auth' => true]);

$router->add('GET', '/admin/google', [GoogleController::class, 'index'], ['auth' => true]);
$router->add('POST', '/admin/google/connect', [GoogleController::class, 'connect'], ['auth' => true]);
$router->add('GET', '/admin/google/callback', [GoogleController::class, 'callback'], ['auth' => true]);
$router->add('POST', '/admin/google/disconnect', [GoogleController::class, 'disconnect'], ['auth' => true]);

