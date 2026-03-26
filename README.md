p# payroad/internal-cash-provider

Internal (manual) cash payment provider for the [Payroad](https://github.com/payroad/payroad-core) platform.

Designed for in-store and counter-based cash collection with no external API dependency.

## Features

- Generates a unique deposit code (`CASH-XXXXXXXX`) for each payment
- Moves attempt to `AWAITING_CONFIRMATION` immediately on initiation
- Cashier manually confirms receipt via admin UI or API
- Instant refund (cashier hands cash back to customer)
- No webhooks required

## Requirements

- PHP 8.2+
- `payroad/payroad-core`

## Installation

```bash
composer require payroad/internal-cash-provider
```

## Configuration

```yaml
# config/packages/payroad.yaml
payroad:
  providers:
    internal_cash:
      factory: Payroad\Provider\InternalCash\InternalCashProviderFactory
```

No API keys needed.

## Payment flow

```
Customer                    Cashier                     Backend
───────────────────────────────────────────────────────────────
POST /api/payments/cash
  ← { depositCode: CASH-A1B2C3D4, amount, currency }
Customer presents code
                        Cashier receives cash
                        POST /api/payments/{id}/confirm-cash
                          { attemptId }
                                                  → Payment SUCCEEDED
```

## Implemented interfaces

| Interface | Description |
|-----------|-------------|
| `CashProviderInterface` | Deposit code generation, instant refund |

---

## Using this as a reference for a real provider

This package is intentionally minimal — no external API, no config, instant outcomes.
Real cash providers (OXXO, Boleto, PayCash, Rapipago) follow the same structure.

### File structure to replicate

```
src/
├── YourCashProviderFactory.php   — reads config, constructs provider
├── YourCashProvider.php          — implements CashProviderInterface
└── Data/
    ├── YourCashAttemptData.php   — implements CashAttemptData
    └── YourCashRefundData.php    — implements CashRefundData
```

### What to implement in each file

**`YourCashProviderFactory`** — implement `ProviderFactoryInterface::create(array $config)`.
Read API keys and URLs from `$config`, pass to the provider constructor.
No config needed? Return `new YourCashProvider()` like this package does.

**`YourCashProvider::initiateCashAttempt()`** — call your API to generate a payment voucher,
wrap the response in `YourCashAttemptData`, then:
```php
$attempt = CashPaymentAttempt::create($id, $paymentId, $providerName, $amount, $data);
$attempt->setProviderReference($apiResponse->voucherId);
$attempt->applyTransition(AttemptStatus::AWAITING_CONFIRMATION, 'voucher_issued');
return $attempt;
```

**`YourCashProvider::parseIncomingWebhook()`** — map provider status strings to domain statuses:
```php
return new WebhookResult(
    providerReference: $payload['voucher_id'],
    newStatus:         AttemptStatus::SUCCEEDED,
    providerStatus:    $payload['status'],
    statusChanged:     true,
);
```
Return `null` for events that don't affect attempt status (e.g. informational notifications).

**`YourCashAttemptData`** — store everything the customer needs to pay: voucher code, barcode,
expiry, payment location network. Must implement `toArray()` and `static fromArray(array): static`
so the infrastructure layer can persist and restore the object.

**`YourCashRefundData`** — store the refund outcome: voucher code for cash pickup, location, etc.
Same serialization requirement.

### Registration in `payroad.yaml`

```yaml
payroad:
    providers:
        your_cash:
            factory: Vendor\YourCash\YourCashProviderFactory
            config:
                api_key:  '%env(YOUR_CASH_API_KEY)%'
                base_url: '%env(YOUR_CASH_BASE_URL)%'
```

The key (`your_cash`) becomes the `providerName` — it must match what `supports()` returns.

### Checklist

- [ ] `supports()` matches the provider name from `payroad.yaml`
- [ ] `initiateCashAttempt()` sets a `providerReference` (used by webhook routing)
- [ ] `AttemptData::toArray()` / `fromArray()` round-trip without data loss
- [ ] `parseIncomingWebhook()` maps all provider statuses — return `null` for unknown ones
- [ ] Refund: sync outcome → apply `RefundStatus::SUCCEEDED` immediately; async → leave at `PENDING`
