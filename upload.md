# Bulk Data Import via Excel / CSV

## Is This a Good Idea?

**Yes — strongly recommended.** Real medical facilities don't start from zero. They arrive at a new system with:

- Negotiated insurance price lists (often 200–500 line items per package)
- A full drug/supply catalog from their previous pharmacy system
- Years of patient demographic records
- Service price lists agreed with the Ministry of Health

Making staff enter these one-by-one through a form is impractical and introduces transcription errors. The **download template → fill → upload** pattern is the industry standard for healthcare system migrations and routine bulk updates (e.g., when an insurer sends a new annual rate schedule).

The system already exports CSV data (dispensing history, facility reports, revenue reports) using native PHP `fputcsv()`, so the infrastructure pattern is already established. No major new infrastructure is needed to add import.

---

## Where Bulk Import Is Useful in This System

### 1. Insurance Package Prices ← Most Urgent

**Why:** Insurers send annual rate schedules as Excel files listing every covered service, drug, and test with its negotiated price. Entering 300+ prices per package one-by-one is not realistic.

**Template columns:**
```
branch_name | billable_type | item_code | item_name | negotiated_price | effective_from | effective_to | status
```

**User story:** "APA Insurance sent me their 2026 rate schedule Excel. I download the template, copy-paste the prices column, upload, and all 280 prices are loaded in seconds."

---

### 2. Facility Services (Charge Master)

**Why:** A hospital's service catalog (consultations, procedures, ward charges, theatre fees) is typically maintained in Excel by finance. When setting up the system, they need to bulk-load hundreds of services with their standard prices.

**Template columns:**
```
service_code | name | category | cost_price | selling_price | is_billable | is_active
```

---

### 3. Drug / Inventory Catalog

**Why:** Pharmacies maintain their formularies in spreadsheets. Importing the drug catalog with generic names, dosage forms, strengths, and default prices avoids weeks of manual data entry.

**Template columns:**
```
name | generic_name | brand_name | item_type | dosage_form | strength | category | unit | default_selling_price | is_controlled | is_active
```

---

### 4. Lab Test Catalog

**Why:** Labs have standard test menus from their analyzers, often shared as spreadsheets with test codes and prices.

**Template columns:**
```
test_code | test_name | category | base_price | is_active
```

---

### 5. Patient Records (Migration Import)

**Why:** Facilities migrating from paper records or a previous HMIS need to bring over patient demographics without re-registering thousands of patients.

**Template columns:**
```
first_name | last_name | date_of_birth | gender | phone | id_type | id_number | insurance_company | insurance_package | insurance_member_number
```

**Special consideration:** Patient import needs strict duplicate detection (match on ID number or phone + DOB) to avoid creating phantom records.

---

### 6. Staff / Users

**Why:** HR departments maintain staff lists in Excel. Bulk-loading staff with roles and branch assignments at go-live saves significant setup time.

**Template columns:**
```
first_name | last_name | email | phone | position | role | branch | department
```

---

## How to Implement It

### Recommended Approach: Two-Step (Validate → Confirm)

The safest pattern for healthcare data:

1. **User uploads the file** → server validates every row and returns a preview table
2. **User reviews the preview** (green rows = valid, red rows = errors with explanations)
3. **User clicks Confirm** → valid rows are committed; errors are shown for correction

This prevents silent data corruption and gives the user confidence before committing.

---

### Package Recommendation

Since the project has no Excel library yet, add **`maatwebsite/excel`** (Laravel Excel):

```bash
composer require maatwebsite/excel
```

**Why this package:**
- De facto standard in the Laravel ecosystem
- Handles `.xlsx`, `.xls`, and `.csv` in one API
- Built-in row-by-row validation via Laravel's validator
- Chunk reading for large files (memory-safe)
- Can generate Excel templates with styled headers and example rows

**Alternative:** If no new dependency is desired, use PHP's native `fgetcsv()` (same pattern as the existing CSV exports) but limit to CSV only and lose the Excel template styling.

---

### Implementation Pattern

#### 1. Download Template

Follow the existing `StreamedResponse` + `fputcsv()` pattern already used in `DispensingHistoryController@export` and `ReportGeneratorController@exportCsv`.

```php
// GET /insurance-packages/{package}/prices/template
public function downloadTemplate(InsurancePackage $package): StreamedResponse
{
    $filename = sprintf('insurance-prices-template-%s.csv', str($package->name)->slug());

    return response()->streamDownload(function () use ($package): void {
        $handle = fopen('php://output', 'w');

        // Header row
        fputcsv($handle, [
            'branch_name',
            'billable_type',       // service | drug | test
            'item_code',           // service_code / test_code
            'item_name',           // must match exactly
            'negotiated_price',
            'effective_from',      // YYYY-MM-DD
            'effective_to',        // YYYY-MM-DD or leave blank
            'status',              // active | inactive
        ]);

        // Example row so user understands the format
        fputcsv($handle, [
            'Main Branch',
            'service',
            'CONS-001',
            'General Consultation',
            '500.00',
            date('Y-m-d'),
            '',
            'active',
        ]);

        fclose($handle);
    }, $filename, ['Content-Type' => 'text/csv']);
}
```

For **Excel** templates (with Laravel Excel), you can add column width, bold headers, dropdown validation for `billable_type` and `status` cells directly in the spreadsheet — which reduces user errors significantly.

---

#### 2. Import Controller

```php
// POST /insurance-packages/{package}/prices/import
public function import(
    ImportInsurancePackagePricesRequest $request,
    InsurancePackage $package,
    ProcessInsurancePackagePricesImport $action
): RedirectResponse {
    $result = $action->handle($package, $request->file('file'));

    if ($result->hasErrors()) {
        return back()
            ->with('import_errors', $result->errors())
            ->with('import_valid_count', $result->validCount());
    }

    return to_route('insurance-packages.show', $package)
        ->with('success', sprintf(
            '%d prices imported successfully.',
            $result->importedCount()
        ));
}
```

---

#### 3. Action Class

The action reads the file, validates each row, resolves item IDs by name/code, and bulk-inserts valid rows.

```php
final readonly class ProcessInsurancePackagePricesImport
{
    public function handle(InsurancePackage $package, UploadedFile $file): ImportResult
    {
        $rows   = $this->parseFile($file);        // returns Collection of arrays
        $errors = collect();
        $valid  = collect();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is the header

            $validator = Validator::make($row, [
                'branch_name'       => ['required', 'string'],
                'billable_type'     => ['required', new Enum(BillableItemType::class)],
                'item_name'         => ['required', 'string'],
                'negotiated_price'  => ['required', 'numeric', 'min:0'],
                'effective_from'    => ['required', 'date'],
                'effective_to'      => ['nullable', 'date', 'after_or_equal:effective_from'],
                'status'            => ['required', new Enum(GeneralStatus::class)],
            ]);

            if ($validator->fails()) {
                $errors->push([
                    'row'    => $rowNumber,
                    'data'   => $row,
                    'errors' => $validator->errors()->all(),
                ]);
                continue;
            }

            // Resolve branch
            $branch = FacilityBranch::query()
                ->where('tenant_id', $package->tenant_id)
                ->where('name', $row['branch_name'])
                ->first();

            if (! $branch) {
                $errors->push(['row' => $rowNumber, 'data' => $row, 'errors' => ["Branch '{$row['branch_name']}' not found."]]);
                continue;
            }

            // Resolve billable item ID by name/code
            $billableId = $this->resolveBillableId($package->tenant_id, $row);

            if (! $billableId) {
                $errors->push(['row' => $rowNumber, 'data' => $row, 'errors' => ["Item '{$row['item_name']}' not found for type '{$row['billable_type']}'."]]);
                continue;
            }

            $valid->push([
                'tenant_id'            => $package->tenant_id,
                'facility_branch_id'   => $branch->id,
                'insurance_package_id' => $package->id,
                'billable_type'        => $row['billable_type'],
                'billable_id'          => $billableId,
                'price'                => $row['negotiated_price'],
                'effective_from'       => $row['effective_from'],
                'effective_to'         => $row['effective_to'] ?: null,
                'status'               => $row['status'],
                'created_by'           => Auth::id(),
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        // Bulk insert valid rows
        if ($valid->isNotEmpty()) {
            InsurancePackagePrice::query()->insert($valid->all());
        }

        return new ImportResult(
            importedCount: $valid->count(),
            errors: $errors->all(),
        );
    }
}
```

---

#### 4. Frontend UI

Add to the show page (or as a separate upload page):

```
┌─────────────────────────────────────────────────────┐
│  Gold Cover — Prices                     [Add Price] │
│                                                      │
│  ┌─ Import Prices ──────────────────────────────┐   │
│  │  1. Download Template  [↓ Download CSV]       │   │
│  │  2. Fill in your prices                       │   │
│  │  3. Upload the file    [Choose file] [Import] │   │
│  └──────────────────────────────────────────────┘   │
│                                                      │
│  ┌─ Existing Prices ────────────────────────────┐   │
│  │  Type     Item          Price   From    To    │   │
│  │  Service  Consultation  500.00  Jan-26  —     │   │
│  └──────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────┘
```

After upload, if there are errors, show them inline:

```
┌─ Import Results ──────────────────────────────────────┐
│  ✓ 47 rows imported successfully                       │
│  ✗ 3 rows had errors:                                  │
│                                                        │
│  Row 5  │ drug │ Amoxicillin 500mg  │ Branch not found │
│  Row 12 │ test │ Full Blood Count   │ Price is missing  │
│  Row 19 │ drug │ Metformin 850mg    │ Item not found    │
│                                                        │
│  [Download Error Report]  [Close]                      │
└────────────────────────────────────────────────────────┘
```

---

#### 5. Routes to Add

```php
// Template download
Route::get('insurance-packages/{insurance_package}/prices/template', [InsurancePackagePriceController::class, 'downloadTemplate'])
    ->name('insurance-packages.prices.template');

// Import upload
Route::post('insurance-packages/{insurance_package}/prices/import', [InsurancePackagePriceController::class, 'import'])
    ->name('insurance-packages.prices.import');
```

Same pattern applies to every other module — just swap the controller, action, and template columns.

---

## Important Implementation Rules

### Item Name Matching
The template uses human-readable names (`Amoxicillin 500mg`) but the DB stores UUIDs. The import action must resolve names → IDs. To reduce mismatches:
- Include `item_code` column (service_code, test_code) as a more reliable lookup key than name
- Trim and lowercase-compare names to handle trailing spaces
- Report unmatched items clearly so the user can correct spelling

### Duplicate / Overlap Prevention
The `NoOverlappingInsurancePriceWindow` validation rule must be applied during import just as it is in the single-entry form. Skip or error rows that would create overlapping date windows for the same item.

### Large File Handling
For files over ~500 rows, use **chunked reading** (Laravel Excel's `WithChunkReading` interface or PHP's `fgetcsv()` in a loop) rather than loading the entire file into memory. Queue the import as a background job and notify the user by notification when complete.

### Tenant Isolation
Every import must scope all lookups (branch, item, existing prices) to the current user's `tenant_id`. Never allow cross-tenant data leakage.

### Audit Trail
Use `created_by = Auth::id()` on all imported rows (already part of the model). Log the import as a Spatie activity log event: `"Imported 280 prices for Gold Cover package"`.

### Error Report Download
After a partially successful import, offer a **"Download Error Report"** CSV listing the failed rows with the original data and error messages, so the user can fix and re-upload just the failed rows.
