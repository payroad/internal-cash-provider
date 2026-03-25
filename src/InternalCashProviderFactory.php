<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCash;

use Payroad\Port\Provider\ProviderFactoryInterface;

final class InternalCashProviderFactory implements ProviderFactoryInterface
{
    public function create(array $config): InternalCashProvider
    {
        return new InternalCashProvider();
    }
}
