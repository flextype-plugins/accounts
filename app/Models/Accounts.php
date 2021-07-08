<?php

declare(strict_types=1);

/**
 * @link https://flextype.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flextype\Plugins\Accounts\Models;

use Atomastic\Arrays\Arrays;
use Atomastic\Macroable\Macroable;

use function array_merge;
use function arrays;
use function filesystem;
use function filter;
use function find;
use function flextype;
use function strings;

class Accounts
{
    use Macroable;

    /**
     * Accounts Storage
     *
     * Used for storing current requested accounts data
     * and allow to change them on fly.
     *
     * @var Arrays
     * @access private
     */
    private Arrays $storage;

    /**
     *  __construct
     */
    public function __construct()
    {
        $this->storage = arrays();
    }

    /**
     * Get Accounts Storage
     *
     * @return Arrays
     */
    public function storage(): Arrays
    {
        return $this->storage;
    }

    /**
     * Fetch.
     *
     * @param string $id      Unique identifier of the account.
     * @param array  $options Options array.
     *
     * @access public
     *
     * @return Arrays Returns instance of The Arrays class with items.
     */
    public function fetch(string $id, array $options = []): Arrays
    {
      // Store data
      $this->storage()->set('fetch.id', $id);
      $this->storage()->set('fetch.options', $options);
      $this->storage()->set('fetch.data', []);

      // Run event: onAccountsFetch
      flextype('emitter')->emit('onAccountsFetch');

      // Single fetch helper
      $single = function ($id, $options) {

          // Store data
          $this->storage()->set('fetch.id', $id);
          $this->storage()->set('fetch.options', $options);
          $this->storage()->set('fetch.data', []);

          // Run event: onAccountsFetchSingle
          flextype('emitter')->emit('onAccountsFetchSingle');

          // Get Cache ID for current requested account
          $accountCacheID = $this->getCacheID($this->storage()->get('fetch.id'));

          // 1. Try to get current requested account from cache
          if (flextype('cache')->has($accountCacheID)) {

              // Fetch account from cache and Apply filter for fetch data
              $this->storage()->set('fetch.data', filter(flextype('cache')->get($accountCacheID),
                                                       $this->storage()->get('fetch.options.filter', [])));

              // Run event: onAccountsFetchSingleCacheHasResult
              flextype('emitter')->emit('onAccountsFetchSingleCacheHasResult');

              // Return account from cache
              return arrays($this->storage()->get('fetch.data'));
          }

          // 2. Try to get current requested account from filesystem
          if ($this->has($this->storage()->get('fetch.id'))) {
              // Get account file location
              $accountFile = $this->getFileLocation($this->storage()->get('fetch.id'));

              // Try to get requested account from the filesystem
              $accountFileContent = filesystem()->file($accountFile)->get();

              if ($accountFileContent === false) {
                  // Run event: onAccountsFetchSingleNoResult
                  flextype('emitter')->emit('onAccountsFetchSingleNoResult');
                  return arrays($this->storage()->get('fetch.data'));
              }

              // Decode account file content
              $this->storage()->set('fetch.data', flextype('serializers')->yaml()->decode($accountFileContent));

              // Run event: onAccountsFetchSingleHasResult
              flextype('emitter')->emit('onAccountsFetchSingleHasResult');

              // Apply filter for fetch data
              $this->storage()->set('fetch.data', filter($this->storage()->get('fetch.data'),
                                                         $this->storage()->get('fetch.options.filter', [])));

              // Set cache state
              $cache = $this->storage()->get('fetch.data.cache.enabled',
                                             flextype('registry')->get('flextype.settings.cache.enabled'));

               // Save account data to cache
              if ($cache) {
                  flextype('cache')->set($accountCacheID, $this->storage()->get('fetch.data'));
              }

              // Return account data
              return arrays($this->storage()->get('fetch.data'));
          }

          // Run event: onAccountsFetchSingleNoResult
          flextype('emitter')->emit('onAccountsFetchSingleNoResult');

          // Return empty array if account is not founded
          return arrays($this->storage()->get('fetch.data'));
      };

      if (isset($this->storage['fetch']['options']['collection']) &&
          strings($this->storage['fetch']['options']['collection'])->isTrue()) {

          // Run event: onAccountsFetchCollection
          flextype('emitter')->emit('onAccountsFetchCollection');

          if (! $this->getDirectoryLocation($id)) {
              // Run event: onAccountsFetchCollectionNoResult
              flextype('emitter')->emit('onAccountsFetchCollectionNoResult');

              // Return accounts array
              return arrays($this->storage()->get('fetch.data'));
          }

          // Find accounts in the filesystem
          $accounts = find($this->getDirectoryLocation($id),
                                                      isset($options['find']) ?
                                                            $options['find'] :
                                                            []);

          // Walk through accounts results
          if ($accounts->hasResults()) {

              $data = [];

              foreach ($accounts as $currentAccount) {
                  if ($currentAccount->getType() !== 'file' || $currentAccount->getFilename() !== 'account.yaml') {
                      continue;
                  }

                  $currentAccountID = strings($currentAccount->getPath())
                                          ->replace('\\', '/')
                                          ->replace(PATH['project'] . '/accounts/', '')
                                          ->trim('/')
                                          ->toString();

                  $data[$currentAccountID] = $single($currentAccountID, [])->toArray();
              }

              $this->storage()->set('fetch.data', $data);

              // Run event: onAccountsFetchCollectionHasResult
              flextype('emitter')->emit('onAccountsFetchCollectionHasResult');

              // Apply filter for fetch data
              $this->storage()->set('fetch.data', filter($this->storage()->get('fetch.data'),
                                                       isset($options['filter']) ?
                                                             $options['filter'] :
                                                             []));
          }

          // Run event: onAccountsFetchCollectionNoResult
          flextype('emitter')->emit('onAccountsFetchCollectionNoResult');

          // Return accounts array
          return arrays($this->storage()->get('fetch.data'));
      } else {
          return $single($this->storage['fetch']['id'],
                         $this->storage['fetch']['options']);
      }
    }

    /**
     * Move account
     *
     * @param string $id    Unique identifier of the account.
     * @param string $newID New Unique identifier of the account.
     *
     * @return bool True on success, false on failure.
     *
     * @access public
     */
    public function move(string $id, string $newID): bool
    {
        // Store data
        $this->storage()->set('move.id', $id);
        $this->storage()->set('move.newID', $newID);

        // Run event: onAccountsMove
        flextype('emitter')->emit('onAccountsMove');

        if (! $this->has($this->storage()->get('move.newID'))) {
            return filesystem()
                        ->directory($this->getDirectoryLocation($this->storage()->get('move.id')))
                        ->move($this->getDirectoryLocation($this->storage()->get('move.newID')));
        }

        return false;
    }

    /**
     * Update account
     *
     * @param string $id   Unique identifier of the account.
     * @param array  $data Data to update for the account.
     *
     * @return bool True on success, false on failure.
     *
     * @access public
     */
    public function update(string $id, array $data): bool
    {
        // Store data
        $this->storage()->set('update.id', $id);
        $this->storage()->set('update.data', $data);

        // Run event: onAccountsUpdate
        flextype('emitter')->emit('onAccountsUpdate');

        $accountFile = $this->getFileLocation($this->storage()->get('update.id'));

        if (filesystem()->file($accountFile)->exists()) {
            $body  = filesystem()->file($accountFile)->get();
            $account = flextype('serializers')->yaml()->decode($body);

            return (bool) filesystem()->file($accountFile)->put(flextype('serializers')->yaml()->encode(array_merge($account, $this->storage()->get('update.data'))));
        }

        return false;
    }

    /**
     * Create account.
     *
     * @param string $id   Unique identifier of the account.
     * @param array  $data Data to create for the account.
     *
     * @return bool True on success, false on failure.
     *
     * @access public
     */
    public function create(string $id, array $data = []): bool
    {
        // Store data
        $this->storage()->set('create.id', $id);
        $this->storage()->set('create.data', $data);

        // Run event: onAccountsCreate
        flextype('emitter')->emit('onAccountsCreate');

        // Create account directory first if it is not exists
        $accountDir = $this->getDirectoryLocation($this->storage()->get('create.id'));

        if (
            ! filesystem()->directory($accountDir)->exists() &&
            ! filesystem()->directory($accountDir)->create()
        ) {
            return false;
        }

        // Create account file
        $accountFile = $accountDir . '/account.yaml';
        if (! filesystem()->file($accountFile)->exists()) {
            return (bool) filesystem()->file($accountFile)->put(flextype('serializers')->yaml()->encode($this->storage()->get('create.data')));
        }

        return false;
    }

    /**
     * Delete account.
     *
     * @param string $id Unique identifier of the account.
     *
     * @return bool True on success, false on failure.
     *
     * @access public
     */
    public function delete(string $id): bool
    {
        // Store data
        $this->storage()->set('delete.id', $id);

        // Run event: onAccountsDelete
        flextype('emitter')->emit('onAccountsDelete');

        return filesystem()
                    ->directory($this->getDirectoryLocation($this->storage()->get('delete.id')))
                    ->delete();
    }

    /**
     * Copy account.
     *
     * @param string $id    Unique identifier of the account.
     * @param string $newID New Unique identifier of the account.
     *
     * @return bool|null True on success, false on failure.
     *
     * @access public
     */
    public function copy(string $id, string $newID): ?bool
    {
        // Store data
        $this->storage()->set('copy.id', $id);
        $this->storage()->set('copy.newID', $newID);

        // Run event: onAccountsCopy
        flextype('emitter')->emit('onAccountsCopy');

        return filesystem()
                    ->directory($this->getDirectoryLocation($this->storage()->get('copy.id')))
                    ->copy($this->getDirectoryLocation($this->storage()->get('copy.newID')));
    }

    /**
     * Check whether account exists
     *
     * @param string $id Unique identifier of the account(accounts).
     *
     * @return bool True on success, false on failure.
     *
     * @access public
     */
    public function has(string $id): bool
    {
        // Store data
        $this->storage()->set('has.id', $id);

        // Run event: onAccountsHas
        flextype('emitter')->emit('onAccountsHas');

        return filesystem()->file($this->getFileLocation($this->storage()->get('has.id')))->exists();
    }

    /**
     * Get account file location
     *
     * @param string $id Unique identifier of the account(accounts).
     *
     * @return string account file location
     *
     * @access public
     */
    public function getFileLocation(string $id): string
    {
        return PATH['project'] . '/accounts/' . $id . '/account.yaml';
    }

    /**
     * Get account directory location
     *
     * @param string $id Unique identifier of the account(accounts).
     *
     * @return string account directory location
     *
     * @access public
     */
    public function getDirectoryLocation(string $id): string
    {
        return PATH['project'] . '/accounts/' . $id;
    }

    /**
     * Get Cache ID for account
     *
     * @param  string $id Unique identifier of the account(accounts).
     *
     * @return string Cache ID
     *
     * @access public
     */
    public function getCacheID(string $id): string
    {
        if (flextype('registry')->get('flextype.settings.cache.enabled') === false) {
            return '';
        }

        $accountFile = $this->getFileLocation($id);

        if (filesystem()->file($accountFile)->exists()) {
            return strings('account' . $accountFile . (filesystem()->file($accountFile)->lastModified() ?: ''))->hash()->toString();
        }

        return strings('account' . $accountFile)->hash()->toString();
    }
}
