# payroad/internal-cash-provider

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
