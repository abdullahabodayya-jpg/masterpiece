<?php

namespace Tests\Feature\Verification;

use Tests\Feature\Marketplace\MarketplaceTestCase;

abstract class VerificationTestCase extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return getenv('FRAME_WORK_REGRESSION') === '1' ? 'frame_work_test_001' : 'frame_verification_test_001';
    }
}
