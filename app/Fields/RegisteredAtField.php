<?php

declare(strict_types=1);

/**
 * Flextype (https://flextype.org)
 * Founded by Sergey Romanenko and maintained by Flextype Community.
 */

if (flextype('registry')->get('plugins.accounts.settings.fields.registered_at.enabled')) {
    flextype('emitter')->addListener('onAccountsCreate', static function (): void {
        if (flextype('accounts')->storage()->get('create.data.registered_at') !== null) {
            return;
        }

        flextype('accounts')->storage()->set('create.data.registered_at', date(flextype('registry')->get('flextype.settings.date_format'), time()));
    });
}
