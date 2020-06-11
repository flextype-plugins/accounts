<?php

namespace Flextype;

use Flextype\Component\Session\Session;

$flextype->emitter->addListener('onEntryAfterInitialized', function() use ($flextype) {

    $entry = $flextype->entries->entry;

    if (isset($entry['access']['roles'])) {
        if (!in_array(Session::get('account_role'), $entry['access']['roles'])) {
            $flextype->entries->entry = [];
        }
    }
});
