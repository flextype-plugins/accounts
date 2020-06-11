<?php

namespace Flextype;

use Flextype\Component\Session\Session;

$flextype->emitter->addListener('onEntryAfterInitialized', function() use ($flextype) {

    $entry = $flextype->entries->entry;

    if (isset($entry['access']['accounts']['uuids'])) {
        if (!in_array(Session::get('account_uuid'), $entry['access']['accounts']['uuid'])) {
            $flextype->entries->entry = [];
        }
    }

    if (isset($entry['access']['accounts']['usernames'])) {
        if (!in_array(Session::get('account_username'), $entry['access']['accounts']['usernames'])) {
            $flextype->entries->entry = [];
        }
    }

    if (isset($entry['access']['accounts']['roles'])) {
        if (!in_array(Session::get('account_role'), $entry['access']['accounts']['roles'])) {
            $flextype->entries->entry = [];
        }
    }
});
