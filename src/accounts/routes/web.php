<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flextype\Middlewares\CsrfMiddleware;
use Flextype\Plugin\Acl\Middlewares\AclIsUserLoggedInMiddleware;
use Flextype\Plugin\Accounts\Controllers\AccountsController;

app()->group('/accounts', function () {
    app()->get('', AccountsController::class . ':index')->setName('accounts.index');
    app()->get('/login', AccountsController::class . ':login')->setName('accounts.login');
    app()->post('/login', AccountsController::class . ':loginProcess')->setName('accounts.loginProcess');
    app()->get('/reset-password', AccountsController::class . ':resetPassword')->setName('accounts.resetPassword');
    app()->post('/reset-password', AccountsController::class . ':resetPasswordProcess')->setName('accounts.resetPasswordProcess');
    app()->get('/new-password/{email}/{hash}', AccountsController::class . ':newPasswordProcess')->setName('accounts.newPasswordProcess');
    app()->get('/registration', AccountsController::class . ':registration')->setName('accounts.registration');
    app()->post('/registration', AccountsController::class . ':registrationProcess')->setName('accounts.registrationProcess');
    app()->get('/profile/{email}', AccountsController::class . ':profile')->setName('accounts.profile');
})->add(new CsrfMiddleware());

app()->group('/accounts', function () {
    app()->post('/logout', AccountsController::class . ':logoutProcess')->setName('accounts.logoutProcess');
    app()->get('/profile/{email}/edit', AccountsController::class . ':profileEdit')->setName('accounts.profileEdit');
    app()->post('/profile/{email}/edit', AccountsController::class . ':profileEditProcess')->setName('accounts.profileEditProcess');
})->add(new AclIsUserLoggedInMiddleware(['redirect' => 'accounts.login']))
  ->add(new CsrfMiddleware());
