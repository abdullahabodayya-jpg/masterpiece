<?php

namespace App\Models\Concerns;

use LogicException;

trait RetainsDomainHistory
{
    public function delete()
    {
        throw new LogicException('Manage this record through its domain lifecycle.');
    }
}
