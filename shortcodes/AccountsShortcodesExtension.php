<?php

declare(strict_types=1);

/**
 * Flextype (http://flextype.org)
 * Founded by Sergey Romanenko and maintained by Flextype Community.
 */

namespace Flextype;

use Thunder\Shortcode\ShortcodeFacade;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

// Shortcode: [userLoggedInUsername]
$flextype['shortcodes']->addHandler('userLoggedInUsername', static function (ShortcodeInterface $s) use ($flextype) {
    if ($flextype->AccountsController->getUserLoggedInUsername()) {
        return $flextype->AccountsController->getUserLoggedInUsername();
    }
    return '';
});

// Shortcode: [userLoggedInUuid]
$flextype['shortcodes']->addHandler('userLoggedInUuid', static function (ShortcodeInterface $s) use ($flextype) {
    if ($flextype->AccountsController->getUserLoggedInUuid()) {
        return $flextype->AccountsController->getUserLoggedInUuid();
    }
    return '';
});

// Shortcode: [userLoggedInRoles]
$flextype['shortcodes']->addHandler('userLoggedInRoles', static function (ShortcodeInterface $s) use ($flextype) {
    if ($flextype->AccountsController->getUserLoggedInRoles()) {
        return $flextype->AccountsController->getUserLoggedInRoles();
    }
    return '';
});

// Shortcode: [userLoggedIn]Private content here..[/userLoggedIn]
$flextype['shortcodes']->addHandler('userLoggedIn', function (ShortcodeInterface $s) use ($flextype) {
    if ($flextype->AccountsController->isUserLoggedIn()) {
        return $s->getContent();
    }
    return '';
});

// Shortcode: [userLoggedInRolesOneOf roles="admin, student"]Private content here..[/userLoggedInRolesOneOf]
$flextype['shortcodes']->addHandler('userLoggedInRolesOneOf', function (ShortcodeInterface $s) use ($flextype) {
    if ($flextype->AccountsController->isUserLoggedInRolesOneOf($s->getParameter('roles'))) {
        return $s->getContent();
    }
    return '';
});

// Shortcode: [userLoggedInUuidOneOf uuids="ea7432a3-b2d5-4b04-b31d-1c5acc7a55e2, d549af27-79a0-44f2-b9b1-e82b47bf87e2"]Private content here..[/userLoggedInUuidOneOf]
$flextype['shortcodes']->addHandler('userLoggedInUuidOneOf', function (ShortcodeInterface $s) use ($flextype) {
    if ($flextype->AccountsController->isUserLoggedInUuidOneOf($s->getParameter('uuids'))) {
        return $s->getContent();
    }
    return '';
});

// Shortcode: [userLoggedInUsernameOneOf usernames="jack, sam"]Private content here..[/userLoggedInUsernameOneOf]
$flextype['shortcodes']->addHandler('userLoggedInUsernameOneOf', function (ShortcodeInterface $s) use ($flextype) {
    if ($flextype->AccountsController->isUserLoggedInUsernameOneOf($s->getParameter('usernames'))) {
        return $s->getContent();
    }
    return '';
});
