<?php

namespace Tests\Feature\Verification;

use Tests\Feature\Marketplace\MarketplaceTestCase;

abstract class VerificationTestCase extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        return 'frame_verification_test_001';
    }
}
