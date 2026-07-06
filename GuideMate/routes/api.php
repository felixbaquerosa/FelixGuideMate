<?php

declare(strict_types=1);

use App\Controllers\ApiController;
use App\Core\Router;

/** @var Router $router */

$router->get('/api/home', [ApiController::class, 'home']);
$router->get('/api/categories', [ApiController::class, 'categories']);
$router->get('/api/listings', [ApiController::class, 'listings']);
$router->get('/api/listings/{slug}', [ApiController::class, 'listing']);

$router->post('/api/auth/login', [ApiController::class, 'login']);
$router->post('/api/auth/register', [ApiController::class, 'register']);
$router->post('/api/auth/forgot-password', [ApiController::class, 'forgotPassword']);
$router->get('/api/auth/me', [ApiController::class, 'me']);
$router->post('/api/auth/logout', [ApiController::class, 'logout']);
$router->post('/api/auth/change-password', [ApiController::class, 'changePassword']);
$router->post('/api/auth/delete-account', [ApiController::class, 'deleteAccount']);

$router->get('/api/bookings', [ApiController::class, 'bookings']);
$router->post('/api/bookings', [ApiController::class, 'createBooking']);
$router->get('/api/trip-map', [ApiController::class, 'tripMap']);

$router->get('/api/favorites', [ApiController::class, 'favorites']);
$router->post('/api/favorites/toggle', [ApiController::class, 'toggleFavorite']);

$router->get('/api/rentals', [ApiController::class, 'rentalUnits']);
$router->post('/api/rentals', [ApiController::class, 'createRental']);

$router->get('/api/feedback', [ApiController::class, 'myFeedback']);
$router->post('/api/feedback', [ApiController::class, 'submitFeedback']);
