<?php

declare(strict_types=1);

/**
 * Flextype (https://flextype.org)
 * Founded by Sergey Romanenko and maintained by Flextype Community.
 */

emitter()->addListener('onEntriesCreate', static function (): void {

    if (! registry()->get('flextype.settings.entries.collections.accounts_item.fields.registered_at.enabled')) {
        return;
    }

    if (entries()->registry()->get('create.data.registered_at') !== null) {
        return;
    }

    entries()->registry()->set('create.data.registered_at', date(registry()->get('flextype.settings.date_format'), time()));
});
