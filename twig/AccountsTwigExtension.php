<?php

declare(strict_types=1);

/**
 * Flextype (https://flextype.org)
 * Founded by Sergey Romanenko and maintained by Flextype Community.
 */

namespace Flextype;

use Twig_Extension;
use Twig_Extension_GlobalsInterface;

class AccountsTwigExtension extends Twig_Extension implements Twig_Extension_GlobalsInterface
{
    /**
     * Flextype Dependency Container
     */
    private $flextype;

    /**
     * Constructor
     */
    public function __construct($flextype)
    {
        $this->flextype = $flextype;
    }

    /**
     * Register Global variables in an extension
     */
    public function getGlobals()
    {
        return [
            'accounts' => new AccountsTwig($this->flextype),
        ];
    }
}

class AccountsTwig
{
    /**
     * Flextype Dependency Container
     */
    private $flextype;

    /**
     * Constructor
     */
    public function __construct($flextype)
    {
        $this->flextype = $flextype;
    }

    public function isUserLoggedIn()
    {
        return $this->flextype->AccountsController->isUserLoggedIn();
    }

    public function getUserLoggedInUsername()
    {
        return $this->flextype->AccountsController->getUserLoggedInUsername();
    }

    public function getUserLoggedInRoles()
    {
        return $this->flextype->AccountsController->getUserLoggedInRoles();
    }

    public function getUserLoggedInUuid()
    {
        return $this->flextype->AccountsController->getUserLoggedInUuid();
    }

    public function isUserLoggedInRolesOneOf($roles)
    {
        return $this->flextype->AccountsController->isUserLoggedInRolesOneOf($roles);
    }
}
