<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype;

$app->group('/accounts', function () use ($app) : void {
    $app->get('', 'AccountsController:index')->setName('accounts.index');
    $app->get('/login', 'AccountsController:login')->setName('accounts.login');
    $app->post('/login', 'AccountsController:loginProcess')->setName('accounts.loginProcess');
    $app->get('/reset-password', 'AccountsController:resetPassword')->setName('accounts.resetPassword');
    $app->post('/reset-password', 'AccountsController:resetPasswordProcess')->setName('accounts.resetPasswordProcess');
    $app->get('/new-password/{username}/{hash}', 'AccountsController:newPasswordProcess')->setName('accounts.newPasswordProcess');
    $app->get('/registration', 'AccountsController:registration')->setName('accounts.registration');
    $app->post('/registration', 'AccountsController:registrationProcess')->setName('accounts.registrationProcess');
    $app->get('/profile/{username}', 'AccountsController:profile')->setName('accounts.profile');
})->add('csrf');

$app->group('/accounts', function () use ($app) : void {
    $app->post('/logout', 'AccountsController:logoutProcess')->setName('accounts.logoutProcess');
    $app->get('/profile/{username}/edit', 'AccountsController:profileEdit')->setName('accounts.profileEdit');
    $app->post('/profile/{username}/edit', 'AccountsController:profileEditProcess')->setName('accounts.profileEditProcess');
})->add(new AclAccountIsUserLoggedInMiddleware(['container' => $flextype, 'redirect' => 'accounts.login']))->add('csrf');
