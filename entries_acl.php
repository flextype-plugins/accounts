<?php

namespace Flextype;

use Flextype\Component\Session\Session;

$flextype->emitter->addListener('onEntryAfterInitialized', function() use ($flextype) {

    $entry = $flextype->entries->entry;

    if (isset($entry['access']['accounts']['uuids'])) {
        if (!in_array(Session::get('account_uuid'), array_map('trim', explode(',', $entry['access']['accounts']['uuid'])))) {
            $flextype->entries->entry = [];
        }
    }

    if (isset($entry['access']['accounts']['usernames'])) {
        if (!in_array(Session::get('account_username'), array_map('trim', explode(',', $entry['access']['accounts']['usernames'])))) {
            $flextype->entries->entry = [];
        }
    }

    if (isset($entry['access']['accounts']['roles'])) {
        $entries_user_roles   = array_map('trim', explode(',', $entry['access']['accounts']['roles']));
        $logged_in_user_roles = array_map('trim', explode(',', Session::get('account_roles')));

        $result = array_intersect($entries_user_roles, $logged_in_user_roles);

        if (empty($result)) {
            $flextype->entries->entry = [];
        }
    }
});
