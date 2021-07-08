<?php

declare(strict_types=1);

/**
 * Flextype (https://flextype.org)
 * Founded by Sergey Romanenko and maintained by Flextype Community.
 */

if (flextype('registry')->get('plugins.accounts.settings.fields.id.enabled')) {
    flextype('emitter')->addListener('onAccountsFetchSingleHasResult', static function (): void {
        if (flextype('accounts')->storage()->get('fetch.data.id') !== null) {
            return;
        }

        flextype('accounts')->storage()->set('fetch.data.id', (string) strings(flextype('accounts')->storage()->get('fetch.id'))->trimSlashes());
    });
}
