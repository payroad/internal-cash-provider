<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCash\Data;

use Payroad\Port\Provider\Cash\CashRefundData;

/**
 * Refund data for manual cash returns.
 * The cashier hands cash back directly — no voucher or external pickup location.
 */
final class InternalCashRefundData implements CashRefundData
{
    public function getRefundVoucherCode(): ?string
    {
        return null;
    }

    public function getPickupLocation(): ?string
    {
        return null;
    }

    public function toArray(): array
    {
        return [];
    }

    public static function fromArray(array $data): static
    {
        return new self();
    }
}
