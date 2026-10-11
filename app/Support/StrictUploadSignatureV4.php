<?php

namespace App\Support;

use Aws\Signature\SignatureV4;

/**
 * Include upload constraints that the AWS PHP SDK deliberately omits from
 * presigned URLs. R2 then rejects a request whose media type or exact HTTP
 * body length differs from the values authorized by the application.
 */
class StrictUploadSignatureV4 extends SignatureV4
{
    /** @return array<string, true> */
    protected function getHeaderBlacklist(): array
    {
        $blacklist = parent::getHeaderBlacklist();
        unset($blacklist['content-length']);

        return $blacklist;
    }

    /** @return array<string, true> */
    protected function getPresignHeaderDenyList(): array
    {
        $denyList = parent::getPresignHeaderDenyList();
        unset($denyList['content-type']);

        return $denyList;
    }
}
