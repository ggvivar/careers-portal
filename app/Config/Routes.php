<?php
use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('AuthController');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

$routes->get('/', 'AuthController::index');
$routes->match(['get', 'post'], 'login', 'AuthController::index');
$routes->post('forgot-password', 'AuthController::forgot');
$routes->get('register', 'AuthController::register');
$routes->post('register', 'AuthController::storeRegistration');
$routes->get('reset-password', 'AuthController::resetForm');
$routes->post('reset-password', 'AuthController::resetPassword');
$routes->get('logout', 'AuthController::logout');

$routes->get('jobs', 'JobsController::index');
$routes->get('jobs/(:num)', 'JobsController::show/$1');

$routes->group('', ['filter' => 'applicantauth'], static function (RouteCollection $routes): void {
    $routes->get('dashboard', 'DashboardController::index');
    $routes->get('saved-jobs', 'JobsController::saved');
    $routes->post('jobs/(:num)/save', 'JobsController::save/$1');
    $routes->post('jobs/(:num)/unsave', 'JobsController::unsave/$1');
    $routes->get('profile', 'ProfileController::index');
    $routes->post('profile', 'ProfileController::update');
    $routes->post('profile/password', 'ProfileController::updatePassword');
    $routes->post('profile/employee-details', 'ProfileController::updateEmployeeDetails');
    $routes->post('profile/education', 'ProfileController::storeEducation');
    $routes->post('profile/education/(:num)/delete', 'ProfileController::deleteEducation/$1');
    $routes->post('profile/employment', 'ProfileController::storeEmployment');
    $routes->post('profile/employment/(:num)/delete', 'ProfileController::deleteEmployment/$1');
    $routes->post('profile/documents', 'ProfileController::storeDocument');
    $routes->get('profile/documents/(:num)/download', 'ProfileController::downloadDocument/$1');
    $routes->post('profile/documents/(:num)/delete', 'ProfileController::deleteDocument/$1');
    $routes->get('apply/(:num)', 'ApplicationsController::create/$1');
    $routes->post('apply/(:num)', 'ApplicationsController::store/$1');
    $routes->get('applications', 'ApplicationsController::index');
    $routes->get('applications/(:num)', 'ApplicationsController::show/$1');
    $routes->post('applications/(:num)/respond', 'ApplicationsController::respond/$1');
    $routes->post('applications/(:num)/withdraw', 'ApplicationsController::withdraw/$1');
    $routes->get('notifications', 'NotificationsController::index');
    $routes->post('notifications/(:num)/read', 'NotificationsController::markRead/$1');
});
