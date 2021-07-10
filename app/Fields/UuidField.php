<?php

declare(strict_types=1);

/**
 * Flextype (https://flextype.org)
 * Founded by Sergey Romanenko and maintained by Flextype Community.
 */

use Ramsey\Uuid\Uuid;

if (flextype('registry')->get('plugins.accounts.settings.fields.uuid.enabled')) {
    flextype('emitter')->addListener('onAccountsCreate', static function (): void {
        if (flextype('accounts')->storage()->get('create.data.uuid') !== null) {
            return;
        }

        flextype('accounts')->storage()->set('create.data.uuid', Uuid::uuid4()->toString());
    });
}
