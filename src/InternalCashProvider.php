<?php

declare(strict_types=1);

namespace Payroad\Provider\InternalCash;

use Payroad\Domain\Attempt\AttemptStatus;
use Payroad\Domain\Attempt\PaymentAttemptId;
use Payroad\Domain\Money\Money;
use Payroad\Domain\Payment\PaymentId;
use Payroad\Domain\PaymentFlow\Cash\CashPaymentAttempt;
use Payroad\Domain\PaymentFlow\Cash\CashRefund;
use Payroad\Domain\Refund\RefundId;
use Payroad\Port\Provider\Cash\CashAttemptContext;
use Payroad\Port\Provider\Cash\CashProviderInterface;
use Payroad\Port\Provider\Cash\CashRefundContext;
use Payroad\Port\Provider\WebhookEvent;
use Payroad\Provider\InternalCash\Data\InternalCashAttemptData;
use Payroad\Provider\InternalCash\Data\InternalCashRefundData;

/**
 * Manual cash payment provider.
 *
 * No external API — the cashier records receipt via the admin UI.
 *
 * Flow:
 *   1. initiateCashAttempt() → AWAITING_CONFIRMATION (waiting for cashier to confirm)
 *   2. Cashier clicks "Confirm cash received" → HandleWebhookUseCase(SUCCEEDED)
 *   3. For refunds: cashier hands cash back → initiateRefund() immediately succeeds
 */
final class InternalCashProvider implements CashProviderInterface
{
    public function supports(string $providerName): bool
    {
        return $providerName === 'internal_cash';
    }

    public function initiateCashAttempt(
        PaymentAttemptId   $id,
        PaymentId          $paymentId,
        string             $providerName,
        Money              $amount,
        CashAttemptContext $context,
    ): CashPaymentAttempt {
        $data    = new InternalCashAttemptData(depositCode: 'CASH-' . strtoupper(substr($id->value, 0, 8)));
        $attempt = CashPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
        $attempt->setProviderReference('cash_' . $id->value);

        // Immediately move to AWAITING_CONFIRMATION — the cashier is waiting to receive payment.
        $attempt->applyTransition(AttemptStatus::AWAITING_CONFIRMATION, 'awaiting_cash_payment');

        return $attempt;
    }

    public function initiateRefund(
        RefundId          $id,
        PaymentId         $paymentId,
        PaymentAttemptId  $originalAttemptId,
        string            $providerName,
        Money             $amount,
        string            $originalProviderReference,
        CashRefundContext  $context,
    ): CashRefund {
        $data   = new InternalCashRefundData();
        $refund = CashRefund::create($id, $paymentId, $originalAttemptId, $providerName, $amount, $data);
        $refund->setProviderReference('cash_refund_' . $id->value);

        return $refund;
    }

    public function parseIncomingWebhook(array $payload, array $headers): ?WebhookEvent
    {
        // Internal provider has no webhooks — confirmation is triggered manually.
        return null;
    }
}
