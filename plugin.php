<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype\Plugins\Accounts;

use Flextype\Plugins\Accounts\Models\Accounts;
use function is_file;

/**
 * Ensure vendor libraries exist
 */
! is_file($accountsAutoload = __DIR__ . '/vendor/autoload.php') and exit('Please run: <i>composer install</i> for accounts plugin');

/**
 * Register The Auto Loader
 *
 * Composer provides a convenient, automatically generated class loader for
 * our application. We just need to utilize it! We'll simply require it
 * into the script here so that we don't have to worry about manual
 * loading any of our classes later on. It feels nice to relax.
 * Register The Auto Loader
 */
$accountsLoader = require_once $accountsAutoload;

/**
 * Include web routes
 */
include_once 'routes/web.php';