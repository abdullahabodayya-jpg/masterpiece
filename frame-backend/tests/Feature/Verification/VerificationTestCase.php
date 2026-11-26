<?php

namespace Tests\Feature\Verification;

use Tests\Feature\Marketplace\MarketplaceTestCase;

abstract class VerificationTestCase extends MarketplaceTestCase
{
    protected function expectedDatabase(): string
    {
        if (getenv('FRAME_PAYMENTS_REGRESSION') === '1') {
            return 'frame_payments_test_002';
        }

        if (getenv('FRAME_MESSAGING_REGRESSION') === '1') {
            return 'frame_messaging_test_001';
        }

        return getenv('FRAME_WORK_REGRESSION') === '1' ? 'frame_work_test_001' : 'frame_verification_test_001';
    }
}
