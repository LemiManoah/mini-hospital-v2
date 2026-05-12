# Reconciliation System — Findings & UI Plan

## How It Works

### Data Model
- **`stock_reconciliations`** — one record per count session: location, date, reason, notes, all workflow timestamps and user FKs, auto-generated number (`REC-YmdHis-XXXX`).
- **`stock_reconciliation_items`** — one row per item counted: `expected_quantity` (system snapshot), `actual_quantity` (what was physically found), `variance_quantity` / `quantity_delta` (the diff), batch info, unit cost, notes.

### Workflow States
```
Draft → Submitted → Reviewed → Approved → Posted
                              ↘ Rejected (can bounce back to Draft)
```
`workflow_status` is derived from timestamps, not just the enum (`status` column only tracks `draft` / `posted` / `cancelled`).

### Actions
| Action | What it does |
|---|---|
| **Create** | Snapshots current ledger quantities into `expected_quantity` per item, calculates variance |
| **Submit** | Sets `submitted_at` / `submitted_by`, sends into review queue |
| **Review** | Sets `reviewed_at` / `reviewed_by`, optional `review_notes` |
| **Approve** | Sets `approved_at` / `approved_by`, optional `approval_notes` |
| **Reject** | Sets `rejected_at` / `rejected_by`, captures `rejection_reason` |
| **Post** | Validates ledger hasn't changed since snapshot, creates `StockMovement` records (AdjustmentGain / AdjustmentLoss), updates balances |

### Business Rules
- Losses require a batch to deduct from; gains can optionally create a new batch.
- Posting is blocked if the item's ledger balance changed after the reconciliation was recorded.
- Variances smaller than 0.0005 are skipped when posting.
- All transitions are audited via `RecordAuditActivity`.

---

## Current UI Problems

### create.tsx
1. **Fat instructional banner** — `<Alert>` with "How to use this form" takes a full card row; users who understand the form read it every time.
2. **Double subtitle** — Two `<p class="text-muted-foreground">` lines under the page heading repeat information already visible in the form fields.
3. **Inline prose in table cells** — The batch column shows "Only needed when actual quantity is lower than system quantity." as a visible box, polluting every gain row.
4. **Overly wide table** — `min-w-[1280px]` with 10 columns including rarely-used "New Batch #", "Expiry Date", and "Notes" columns always visible.

### show.tsx
1. **All workflow timestamps shown at once** — Submitted / Reviewed / Approved / Rejected display even when null (shows "-"), creating visual noise.
2. **All three note fields visible simultaneously** — Review Notes, Approval Notes, and Rejection Reason are always rendered in a row, almost always showing "-".
3. **Action forms embedded in the detail card** — The review textarea, approve textarea, and reject input are rendered inside the info card body, making the page feel like a long scroll of unrelated things.
4. **Multiple action forms visible at once** — A user with `can_review` AND `can_reject` sees both forms inline, cluttered together.
5. **Repeated table in two dialogs** — Submit dialog and Post dialog contain identical table code (Old Qty / New Qty / Variance).
6. **Status section doesn't stand out** — Status badge is buried in a 4-column grid alongside Date, Posted At, Location.

### index.tsx
- Mostly fine. Missing the reconciliation number column — users see Location + Date + Reason but can't identify the record by number.

---

## New UI Plan

### Index Page — Minor Cleanup
- Add **Reconciliation #** as the first column (clickable link).
- Remove the separate "View" button — make the row itself clickable.
- Keep search + status filter as-is.

---

### Create Page — Declutter
- **Remove the Alert banner entirely.** The label/placeholder text is sufficient.
- **Remove the double subtitle.** One short line max under the heading, or none.
- **Replace the inline prose in the batch cell** with a dimmed placeholder inside the `SearchableSelect` — already has `placeholder="Select batch for loss"`.
- **Collapse optional columns** — "New Batch #", "Expiry Date", "Notes" into an expandable row detail (a small chevron per row that reveals extra fields inline). Only show them when variance is a gain or user explicitly expands.
- Table stays wide but only shows: Item | System Qty | Actual Qty | Variance | Batch | Unit Cost | (expand) | Delete.

---

### Show Page — Restructure

#### Header strip (always visible)
```
[REC-240101-0001]   [Main Pharmacy]   [Cycle Count]        [Submitted ▾]
                    May 12, 2026
                                                      [Submit For Review]
```
- Reconciliation number (bold, large)
- Location and date (muted, smaller)
- Reason (muted)
- Status badge (right side, prominent)
- **Single primary action button** on the far right — only the relevant next action for the current stage

#### Workflow progress bar
```
● Draft → ● Submitted → ● Reviewed → ● Approved → ○ Posted
```
- Simple step indicator with dates on completed steps.
- Only show rejection as a red branch if `rejected_at` is set.
- No null timestamps — only completed steps have dates.

#### Notes section — conditional
- Only render the relevant note for the current state:
  - If reviewed: show Review Notes.
  - If approved: show Approval Notes.
  - If rejected: show Rejection Reason (highlighted in red).
  - If draft/submitted: hide entirely.

#### Action area — one action at a time
- Replace the inline forms with a single **action panel** that matches the current stage:
  - `can_submit` → single "Submit For Review" button (opens existing dialog).
  - `can_review` → textarea + "Mark Reviewed" button.
  - `can_approve` + `can_reject` → two side-by-side panels: Approve (textarea + button) | Reject (input + destructive button).
  - `can_post` → "Post Reconciliation" button (opens existing dialog).
- Place the action panel **below the workflow bar**, separated visually, not buried in the detail card.

#### Reconciliation Lines table
- Unchanged columns: Item | Batch | System Qty | Actual Qty | Variance | Unit Cost | Notes.
- Color the Variance cell: red text for loss (< 0), green for gain (> 0), muted for zero.

#### Audit log
- Keep `<AuditTimelineCard>` at the bottom, unchanged.

---

### Component changes needed
- `show.tsx` — restructure into Header + WorkflowBar + ActionPanel + LinesTable + AuditLog sections.
- `create.tsx` — remove Alert, remove subtitles, collapse optional columns per row.
- `index.tsx` — add Reconciliation # column, make rows clickable.
- No backend changes required.
