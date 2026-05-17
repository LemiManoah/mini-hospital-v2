# Currency Implementation Plan

## Goal

Move currency setup out of General Settings and into an Administration currency workspace where each active facility branch can enable or disable multi-currency, choose supported currencies, maintain exchange rates, and use enabled currencies in payment and printable billing outputs.

## Implementation Steps

1. Fix static analysis in charge resolution by normalizing charge-master effective dates before comparing them.
2. Remove the default currency selector from General Settings; the branch base currency remains on the facility branch record.
3. Add branch-level multi-currency state with `facility_branches.multi_currency_enabled`.
4. Add a branch currency selection table so each branch can maintain the currencies it accepts in addition to its base currency.
5. Scope exchange rates to the active branch and only allow rates between currencies selected for that branch.
6. Add an Administration > Currencies page that first shows the multi-currency enable/disable switch, then reveals selected currencies and exchange-rate CRUD when enabled.
7. Wire the Administration sidebar and master-data section to the new currency workspace.
8. Let OPD payments choose a tender currency when multi-currency is enabled, convert the tender amount into the branch base currency for billing balances, and store the tender amount, tender currency, and exchange rate used.
9. Update receipt printing to show the tender currency when the payment was collected in a non-base currency.
10. Keep tests local for the user to run: targeted PHPStan, currency controller tests, finance OPD payment tests, and affected frontend type/build checks.

## Data Model

- `facility_branches.multi_currency_enabled`: controls whether branch users can collect and report in multiple currencies.
- `facility_branch_currencies`: branch-selected currencies, including the base branch currency as the default row.
- `currency_exchange_rates.facility_branch_id`: keeps rates branch-specific.
- `payments.currency_id`, `payments.tender_amount`, `payments.exchange_rate`: preserve the currency used at collection time while keeping `payments.amount` as the branch base-currency amount.

## User Flow

1. User opens Administration > Currencies.
2. If multi-currency is disabled, only the switch and branch base currency are shown.
3. When enabled, the selected currency table and exchange-rate table are displayed.
4. User can add or remove accepted currencies, keeping the branch base currency protected.
5. User can add or remove branch-scoped rates.
6. Payment forms show a currency selector only when multi-currency is enabled and more than one branch currency is selected.
7. Receipts show the base amount and tender amount when they differ.
