<?php
/** Public website routes — all clean URLs. @var App\Core\Router $router */

use App\Controllers\AffiliateController;
use App\Controllers\BlogController;
use App\Controllers\FormController;
use App\Controllers\HomeController;
use App\Controllers\LearnController;
use App\Controllers\PageController;
use App\Controllers\SeoController;
use App\Controllers\ServiceController;
use App\Controllers\SetupController;
use App\Controllers\VerifyController;

$router->get('/setup', [SetupController::class, 'show']);
$router->post('/setup', [SetupController::class, 'install']);

$router->get('/', [HomeController::class, 'index']);
$router->get('/services', [ServiceController::class, 'index']);
$router->get('/services/{slug}', [ServiceController::class, 'show']);

$router->get('/learn', [LearnController::class, 'index']);
$router->get('/learn/{slug}', [LearnController::class, 'show']);

$router->get('/blog', [BlogController::class, 'index']);
$router->get('/blog/page/{n}', [BlogController::class, 'index']);
$router->get('/blog/category/{slug}', [BlogController::class, 'category']);
$router->get('/blog/category/{slug}/page/{n}', [BlogController::class, 'category']);
$router->get('/blog/tag/{slug}', [BlogController::class, 'tag']);
$router->get('/blog/tag/{slug}/page/{n}', [BlogController::class, 'tag']);
$router->get('/blog/{slug}', [BlogController::class, 'show']);

$router->get('/contact', [FormController::class, 'contact']);
$router->post('/contact', [FormController::class, 'contactSubmit']);

$router->get('/verify/{code}', [VerifyController::class, 'show']);
$router->get('/affiliates', [AffiliateController::class, 'page']);
$router->post('/affiliates', [AffiliateController::class, 'apply']);
$router->get('/r/{code}', [AffiliateController::class, 'track']);

$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);

// CMS pages: /about, /privacy-policy, /terms-and-conditions and any page created in the Control Panel.
$router->get('/{slug}', [PageController::class, 'show']);
