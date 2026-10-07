<?php
/** Member accounts, the trading terminal (/terminal), billing and webhooks. @var App\Core\Router $router */

use App\Controllers\BillingController;
use App\Controllers\MemberAuthController as A;
use App\Controllers\Terminal\AccountsController;
use App\Controllers\Terminal\CalculatorController;
use App\Controllers\Terminal\CoachController;
use App\Controllers\Terminal\EdgeController;
use App\Controllers\Terminal\HomeController as Hub;
use App\Controllers\Terminal\InsightsController;
use App\Controllers\Terminal\NotepadController;
use App\Controllers\Terminal\SettingsController;
use App\Controllers\Terminal\StrategyController;
use App\Controllers\Terminal\TradeController;
use App\Controllers\WebhookController;

$router->get('/login', [A::class, 'loginForm']);
$router->post('/login', [A::class, 'login']);
$router->get('/signup', [A::class, 'signupForm']);
$router->post('/signup', [A::class, 'signup']);
$router->post('/logout', [A::class, 'logout']);
$router->get('/auth/google', [A::class, 'google']);
$router->get('/auth/google/callback', [A::class, 'googleCallback']);
$router->get('/forgot-password', [A::class, 'forgotForm']);
$router->post('/forgot-password', [A::class, 'forgot']);
$router->get('/reset-password/{token}', [A::class, 'resetForm']);
$router->post('/reset-password/{token}', [A::class, 'reset']);
$router->get('/onboarding', [A::class, 'onboarding']);
$router->post('/onboarding', [A::class, 'completeOnboarding']);

// Terminal
$router->get('/terminal', [Hub::class, 'index']);
$router->post('/terminal/quick-trade/parse', [Hub::class, 'parse']);
$router->post('/terminal/quick-trade', [Hub::class, 'quickTrade']);
$router->post('/terminal/checklist', [Hub::class, 'checklist']);
$router->post('/terminal/timezone', [Hub::class, 'timezone']);
$router->get('/terminal/calculator', [CalculatorController::class, 'index']);
$router->post('/terminal/calculator/compute', [CalculatorController::class, 'compute']);
$router->post('/terminal/account/switch', [Hub::class, 'switchAccount']);
$router->post('/terminal/demo/load', [Hub::class, 'loadDemo']);

$router->get('/terminal/dashboard', [InsightsController::class, 'dashboard']);
$router->get('/terminal/calendar', [InsightsController::class, 'calendar']);

$router->get('/terminal/trades', [TradeController::class, 'index']);
$router->get('/terminal/trades/export', [TradeController::class, 'export']);
$router->get('/terminal/trades/new', [TradeController::class, 'create']);
$router->post('/terminal/trades', [TradeController::class, 'store']);
$router->get('/terminal/trades/{id}', [TradeController::class, 'show']);
$router->get('/terminal/trades/{id}/edit', [TradeController::class, 'edit']);
$router->post('/terminal/trades/{id}', [TradeController::class, 'update']);
$router->post('/terminal/trades/{id}/delete', [TradeController::class, 'delete']);
$router->post('/terminal/trades/{id}/runner', [TradeController::class, 'runner']);
$router->get('/terminal/trades/{id}/screenshot', [TradeController::class, 'screenshot']);
$router->post('/terminal/trades/{id}/screenshot', [TradeController::class, 'uploadScreenshot']);
$router->post('/terminal/trades/{id}/screenshot/delete', [TradeController::class, 'deleteScreenshot']);

$router->get('/terminal/strategies', [StrategyController::class, 'index']);
$router->get('/terminal/strategies/new', [StrategyController::class, 'create']);
$router->get('/terminal/strategies/{id}/edit', [StrategyController::class, 'edit']);
$router->post('/terminal/strategies', [StrategyController::class, 'store']);
$router->post('/terminal/strategies/{id}', [StrategyController::class, 'update']);
$router->post('/terminal/strategies/{id}/delete', [StrategyController::class, 'delete']);

$router->get('/terminal/edge', [EdgeController::class, 'index']);

$router->get('/terminal/notepad', [NotepadController::class, 'index']);
$router->post('/terminal/notepad', [NotepadController::class, 'save']);

$router->get('/terminal/coach', [CoachController::class, 'index']);
$router->get('/terminal/coach/{id}', [CoachController::class, 'index']);
$router->post('/terminal/coach/send', [CoachController::class, 'send']);
$router->post('/terminal/coach/review', [CoachController::class, 'review']);
$router->post('/terminal/coach/{id}/delete', [CoachController::class, 'delete']);

$router->get('/terminal/accounts', [AccountsController::class, 'index']);
$router->post('/terminal/accounts', [AccountsController::class, 'store']);
$router->post('/terminal/accounts/{id}', [AccountsController::class, 'update']);
$router->post('/terminal/accounts/{id}/archive', [AccountsController::class, 'archive']);
$router->post('/terminal/accounts/{id}/capital', [AccountsController::class, 'capital']);
$router->post('/terminal/accounts/{id}/import', [AccountsController::class, 'import']);

$router->get('/terminal/settings', [SettingsController::class, 'index']);
$router->post('/terminal/settings', [SettingsController::class, 'save']);
$router->post('/terminal/settings/password', [SettingsController::class, 'password']);
$router->get('/terminal/settings/export', [SettingsController::class, 'export']);
$router->post('/terminal/settings/delete', [SettingsController::class, 'deleteAccount']);
$router->post('/terminal/theme', [SettingsController::class, 'theme']);
$router->get('/terminal/billing', [BillingController::class, 'billing']);

// Pricing & payments
$router->get('/pricing', [BillingController::class, 'pricing']);
$router->get('/checkout/{plan}', [BillingController::class, 'checkout']);
$router->post('/checkout/{plan}', [BillingController::class, 'startPayment']);
$router->post('/checkout/razorpay/verify', [BillingController::class, 'razorpayVerify']);
$router->get('/checkout/stripe/success', [BillingController::class, 'stripeSuccess']);
$router->post('/webhooks/razorpay', [WebhookController::class, 'razorpay']);
$router->post('/webhooks/stripe', [WebhookController::class, 'stripe']);
