<?php

namespace Flextype;

use Flextype\Component\Session\Session;

$flextype->emitter->addListener('onEntryAfterInitialized', function() use ($flextype) {

    // Get current entry
    $entry = $flextype->entries->entry;

    if (isset($entry['access']['accounts']['uuids'])) {
        if (!$flextype->AccountsController->isUserLoggedInUuidsOneOf($entry['access']['accounts']['uuids'])) {
            $flextype->entries->entry = [];
        }
    }

    if (isset($entry['access']['accounts']['usernames'])) {
        if (!$flextype->AccountsController->isUserLoggedInUsernameOneOf($entry['access']['accounts']['usernames'])) {
            $flextype->entries->entry = [];
        }
    }

    if (isset($entry['access']['accounts']['roles'])) {
        if (!$flextype->AccountsController->isUserLoggedInRolesOneOf($entry['access']['accounts']['roles'])) {
            $flextype->entries->entry = [];
        }
    }
});
