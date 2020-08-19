<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Flextype\Plugin\Acl\Middlewares\AclIsUserLoggedInMiddleware;

$flextype->group('/accounts', function () use ($flextype) {
    $flextype->get('', 'AccountsController:index')->setName('accounts.index');
    $flextype->get('/login', 'AccountsController:login')->setName('accounts.login');
    $flextype->post('/login', 'AccountsController:loginProcess')->setName('accounts.loginProcess');
    $flextype->get('/reset-password', 'AccountsController:resetPassword')->setName('accounts.resetPassword');
    $flextype->post('/reset-password', 'AccountsController:resetPasswordProcess')->setName('accounts.resetPasswordProcess');
    $flextype->get('/new-password/{email}/{hash}', 'AccountsController:newPasswordProcess')->setName('accounts.newPasswordProcess');
    $flextype->get('/registration', 'AccountsController:registration')->setName('accounts.registration');
    $flextype->post('/registration', 'AccountsController:registrationProcess')->setName('accounts.registrationProcess');
    $flextype->get('/profile/{email}', 'AccountsController:profile')->setName('accounts.profile');
})->add('csrf');

$flextype->group('/accounts', function () use ($flextype) {
    $flextype->post('/logout', 'AccountsController:logoutProcess')->setName('accounts.logoutProcess');
    $flextype->get('/profile/{email}/edit', 'AccountsController:profileEdit')->setName('accounts.profileEdit');
    $flextype->post('/profile/{email}/edit', 'AccountsController:profileEditProcess')->setName('accounts.profileEditProcess');
})->add(new AclIsUserLoggedInMiddleware($flextype, ['redirect' => 'accounts.login']))->add('csrf');
