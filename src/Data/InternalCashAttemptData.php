<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCash\Data;

use Payroad\Domain\PaymentFlow\Cash\CashAttemptData;

/**
 * Attempt data for manual cash payments.
 * No external deposit code or location — the cashier records receipt directly.
 */
final class InternalCashAttemptData implements CashAttemptData
{
    public function __construct(
        private readonly string $depositCode,
        private readonly string $depositLocation = 'In-store cashier',
    ) {}

    public function getDepositCode(): string
    {
        return $this->depositCode;
    }

    public function getDepositLocation(): string
    {
        return $this->depositLocation;
    }

    public function toArray(): array
    {
        return [
            'depositCode'     => $this->depositCode,
            'depositLocation' => $this->depositLocation,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            depositCode:     $data['depositCode']     ?? '',
            depositLocation: $data['depositLocation'] ?? 'In-store cashier',
        );
    }
}
