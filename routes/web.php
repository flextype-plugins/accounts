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
    $app->get('/registration', 'AccountsController:registration')->setName('accounts.registration');
    $app->post('/registration', 'AccountsController:registrationProcess')->setName('accounts.registrationProcess');
})->add('csrf');

$app->group('/accounts', function () use ($app) : void {
    $app->get('/profile', 'AccountsController:profile')->setName('accounts.profile');
    $app->post('/logout', 'AccountsController:logoutProcess')->setName('accounts.logoutProcess');
})->add(new AccountsAuthMiddleware($flextype))->add('csrf');
