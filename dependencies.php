<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype\Plugin\Accounts;

use Flextype\Plugin\Accounts\Controllers\AccountsController;

/**
 * Add accounts controller to Flextype container
 */
flextype()->container()['AccountsController'] = static function () {
    return new AccountsController();
};
