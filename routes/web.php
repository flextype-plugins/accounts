<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flextype\Plugin\Acl\Middlewares\AclIsUserLoggedInMiddleware;
use Flextype\Plugin\Accounts\Controllers\AccountsController;

flextype()->group('/accounts', function () {
    flextype()->get('', AccountsController::class . ':index')->setName('accounts.index');
    flextype()->get('/login', AccountsController::class . ':login')->setName('accounts.login');
    flextype()->post('/login', AccountsController::class . ':loginProcess')->setName('accounts.loginProcess');
    flextype()->get('/reset-password', AccountsController::class . ':resetPassword')->setName('accounts.resetPassword');
    flextype()->post('/reset-password', AccountsController::class . ':resetPasswordProcess')->setName('accounts.resetPasswordProcess');
    flextype()->get('/new-password/{email}/{hash}', AccountsController::class . ':newPasswordProcess')->setName('accounts.newPasswordProcess');
    flextype()->get('/registration', AccountsController::class . ':registration')->setName('accounts.registration');
    flextype()->post('/registration', AccountsController::class . ':registrationProcess')->setName('accounts.registrationProcess');
    flextype()->get('/profile/{email}', AccountsController::class . ':profile')->setName('accounts.profile');
})->add('csrf');

flextype()->group('/accounts', function () {
    flextype()->post('/logout', AccountsController::class . ':logoutProcess')->setName('accounts.logoutProcess');
    flextype()->get('/profile/{email}/edit', AccountsController::class . ':profileEdit')->setName('accounts.profileEdit');
    flextype()->post('/profile/{email}/edit', AccountsController::class . ':profileEditProcess')->setName('accounts.profileEditProcess');
})->add(new AclIsUserLoggedInMiddleware(['redirect' => 'accounts.login']))->add('csrf');
