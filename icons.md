# Patient Payer Icons

## Goal

Show a tiny payer icon beside patient names so staff can quickly distinguish cash patients from insured patients without spending a table column on payer text.

## Design

- Use one reusable React component for the indicator.
- Show a green wallet-style icon for settled cash patients, a yellow wallet-style icon for cash patients with unpaid balances, and a blue shield-style icon for insured patients.
- Keep the icon small, muted, and inline with the patient name.
- Use a tooltip for every icon. Settled cash can read `Cash patient`; unpaid cash should include the unpaid balance; insurance should show the company and package when available.
- Use accessible labels so the meaning is not color-dependent.
- Replace payer columns where the payer column only repeats `cash` or insurer name and the patient name is already present.

## Implementation Plan

1. Add `PatientPayerIndicator` in `resources/js/components`.
2. Apply it to OPD payment queue rows and remove the dedicated payer column.
3. Apply it to OPD payment detail header next to the patient name.
4. Apply it to debtor queue rows and debtor detail header, replacing the payer column where possible.
5. Apply it to active visit and patient visit-history surfaces that already expose payer summaries.
6. Extend debtor payloads with `insurance_package_name` so the tooltip is useful.
7. Update focused feature assertions for the OPD payload and run the TypeScript check.

## Later Rollout

- Patient visit history cards.
- Pharmacy and laboratory queues once their controller payloads expose payer summaries.
- Reports only where payer is not already a primary report dimension.
