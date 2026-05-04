<?php

declare(strict_types=1);

use App\Enums\GeneralStatus;
use App\Enums\InventoryItemType;
use App\Enums\UnitType;
use App\Models\FacilityBranch;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Unit;
use App\Models\User;
use App\Support\BranchContext;
use Database\Seeders\PermissionSeeder;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function (): void {
    $this->seed(PermissionSeeder::class);
});

function createDataUploadContext(): array
{
    $tenantContext = seedTenantContext();

    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'currency_id' => $tenantContext['currency_id'],
        'name' => 'City General Hospital',
        'branch_code' => 'CGH-MAIN',
        'status' => GeneralStatus::ACTIVE,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo('patients.create');

    return [$tenantContext['tenant_id'], $branch, $user];
}

function makeCsvUpload(string $csvContent, string $filename = 'patients.csv'): UploadedFile
{
    $tmpPath = tempnam(sys_get_temp_dir(), 'import_').'.csv';
    file_put_contents($tmpPath, $csvContent);

    return new UploadedFile($tmpPath, $filename, 'text/csv', null, true);
}

/**
 * @param  list<list<mixed>>  $rows
 */
function makeXlsxUpload(array $rows, string $filename = 'patients.xlsx'): UploadedFile
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    foreach ($rows as $rowIndex => $row) {
        foreach ($row as $columnIndex => $value) {
            $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
        }
    }

    $tmpPath = tempnam(sys_get_temp_dir(), 'import_');
    $writer = new Xlsx($spreadsheet);
    $writer->save($tmpPath);

    return new UploadedFile(
        $tmpPath,
        $filename,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

function createInventoryUnit(string $tenantId, string $name = 'Tablet', string $symbol = 'tab'): Unit
{
    return Unit::query()->create([
        'tenant_id' => $tenantId,
        'name' => $name,
        'symbol' => $symbol,
        'type' => UnitType::COUNT,
    ]);
}

it('renders the data upload index page', function (): void {
    [, , $user] = createDataUploadContext();

    $this->actingAs($user)
        ->get('/data-upload')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('data-upload/index')
            ->where('importResult', null),
        );
});

it('returns 403 when the user lacks patients.create permission', function (): void {
    $tenantContext = seedTenantContext();
    $user = User::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('/data-upload/patients/template')
        ->assertForbidden();
});

it('downloads the patient import template', function (): void {
    [, , $user] = createDataUploadContext();

    $this->actingAs($user)
        ->get('/data-upload/patients/template')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('downloads inventory item import templates', function (): void {
    [, , $user] = createDataUploadContext();
    $user->givePermissionTo('inventory_items.create');

    $this->actingAs($user)
        ->get('/data-upload/inventory/drugs/template')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($user)
        ->get('/data-upload/inventory/consumables/template')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('imports valid patients from a csv file', function (): void {
    [$tenantId, $branch, $user] = createDataUploadContext();

    $this->actingAs($user)->withSession([BranchContext::SESSION_KEY => $branch->id]);

    $csv = implode("\n", [
        'first_name,last_name,middle_name,date_of_birth,gender,phone_number,alternative_phone,email,marital_status,blood_group,occupation,religion,next_of_kin_name,next_of_kin_phone,next_of_kin_relationship',
        'Jane,Doe,Mary,1990-05-15,female,+254712345678,,jane.doe@example.com,married,O+,Teacher,christian,John Doe,+254798765432,spouse',
        'John,Smith,,1985-03-20,male,+254798000001,,,,,,,,, ',
    ]);

    $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->post('/data-upload/patients/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    expect(Patient::query()->where('tenant_id', $tenantId)->count())->toBe(2);
    expect(Patient::query()->where('phone_number', '+254712345678')->exists())->toBeTrue();
    expect(Patient::query()->where('phone_number', '+254798000001')->exists())->toBeTrue();
    expect(Patient::query()->where('phone_number', '+254712345678')->first()?->patient_number)->toBe('CGH-PAT-1001');
});

it('imports valid drugs from a csv file', function (): void {
    [$tenantId, , $user] = createDataUploadContext();
    $user->givePermissionTo('inventory_items.create');
    createInventoryUnit($tenantId);

    $csv = implode("\n", [
        'generic_name,brand_name,category,strength,dosage_form,unit,minimum_stock_level,reorder_level,default_purchase_price,default_selling_price,manufacturer,expires,is_controlled,schedule_class,therapeutic_classes,description,is_active',
        'Paracetamol,Panadol,analgesic,500mg,tablet,tab,800,1200,120,300,GSK,true,false,,Analgesic,Analgesic and antipyretic,true',
        'Amoxicillin,Amoxil,antibiotic,500mg,capsule,tab,500,900,180,500,GSK,true,false,,Antibiotic,Common oral antibiotic,true',
    ]);

    $response = $this->actingAs($user)
        ->post('/data-upload/inventory/drugs/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    $result = $response->getSession()->get('import_result');

    expect($result['imported'])->toBe(2);
    expect($result['skipped'])->toBe(0);
    expect(InventoryItem::query()->where('tenant_id', $tenantId)->where('item_type', InventoryItemType::DRUG->value)->count())->toBe(2);
    expect(InventoryItem::query()->where('generic_name', 'Paracetamol')->first()?->unit?->symbol)->toBe('tab');
});

it('imports valid consumables from a csv file', function (): void {
    [$tenantId, , $user] = createDataUploadContext();
    $user->givePermissionTo('inventory_items.create');
    createInventoryUnit($tenantId, 'Sachet', 'sachet');

    $csv = implode("\n", [
        'name,unit,minimum_stock_level,reorder_level,default_purchase_price,default_selling_price,manufacturer,expires,description,is_active',
        'Examination Gloves,sachet,120,200,18000,,SafeTouch,true,Single-use gloves,true',
        '5ml Syringe,sachet,200,350,250,,Becton Dickinson,true,Routine disposable syringe,true',
    ]);

    $response = $this->actingAs($user)
        ->post('/data-upload/inventory/consumables/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    $result = $response->getSession()->get('import_result');

    expect($result['imported'])->toBe(2);
    expect($result['skipped'])->toBe(0);
    expect(InventoryItem::query()->where('tenant_id', $tenantId)->where('item_type', InventoryItemType::CONSUMABLE->value)->count())->toBe(2);
});

it('skips duplicate inventory items in the uploaded file', function (): void {
    [$tenantId, , $user] = createDataUploadContext();
    $user->givePermissionTo('inventory_items.create');
    createInventoryUnit($tenantId);

    $csv = implode("\n", [
        'generic_name,brand_name,category,strength,dosage_form,unit,minimum_stock_level,reorder_level,default_purchase_price,default_selling_price,manufacturer,expires,is_controlled,schedule_class,therapeutic_classes,description,is_active',
        'Paracetamol,Panadol,analgesic,500mg,tablet,tab,800,1200,120,300,GSK,true,false,,Analgesic,Analgesic and antipyretic,true',
        'Paracetamol,Panadol,analgesic,500mg,tablet,tab,800,1200,120,300,GSK,true,false,,Analgesic,Duplicate,true',
    ]);

    $response = $this->actingAs($user)
        ->post('/data-upload/inventory/drugs/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    $result = $response->getSession()->get('import_result');

    expect($result['imported'])->toBe(1);
    expect($result['skipped'])->toBe(1);
    expect($result['errors'][0]['messages'])->toContain('This item appears more than once in the uploaded file.');
    expect(InventoryItem::query()->where('tenant_id', $tenantId)->where('generic_name', 'Paracetamol')->count())->toBe(1);
});

it('continues qroo patient numbering from the branch code during import', function (): void {
    $tenantContext = seedTenantContext();

    $branch = FacilityBranch::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'currency_id' => $tenantContext['currency_id'],
        'name' => 'Main Branch',
        'branch_code' => 'QMC-MAIN',
        'status' => GeneralStatus::ACTIVE,
    ]);

    $user = User::factory()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'email_verified_at' => now(),
    ]);

    $user->givePermissionTo('patients.create');

    Patient::query()->create([
        'tenant_id' => $tenantContext['tenant_id'],
        'patient_number' => 'QMC-PAT-1047',
        'first_name' => 'Existing',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+256700000001',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $csv = implode("\n", [
        'first_name,last_name,middle_name,date_of_birth,gender,phone_number,alternative_phone,email,marital_status,blood_group,occupation,religion,next_of_kin_name,next_of_kin_phone,next_of_kin_relationship',
        'Jane,Doe,,1990-05-15,female,+254712345678,,,,,,,,, ',
    ]);

    $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->post('/data-upload/patients/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    expect(Patient::query()->where('phone_number', '+254712345678')->first()?->patient_number)->toBe('QMC-PAT-1048');
});

it('imports valid patients from an xlsx file with excel date cells', function (): void {
    [$tenantId, $branch, $user] = createDataUploadContext();

    $upload = makeXlsxUpload([
        [
            'first_name',
            'last_name',
            'middle_name',
            'date_of_birth',
            'gender',
            'phone_number',
            'alternative_phone',
            'email',
            'marital_status',
            'blood_group',
            'occupation',
            'religion',
            'next_of_kin_name',
            'next_of_kin_phone',
            'next_of_kin_relationship',
        ],
        [
            'Jane',
            'Doe',
            'Mary',
            new DateTimeImmutable('1990-05-15'),
            'female',
            '+254712345678',
            null,
            'jane.doe@example.com',
            'married',
            'O+',
            'Teacher',
            'christian',
            'John Doe',
            '+254798765432',
            'spouse',
        ],
    ]);

    $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->post('/data-upload/patients/import', ['file' => $upload])
        ->assertRedirect('/data-upload');

    $patient = Patient::query()
        ->where('tenant_id', $tenantId)
        ->where('phone_number', '+254712345678')
        ->firstOrFail();

    expect($patient->date_of_birth?->toDateString())->toBe('1990-05-15');
});

it('skips rows with validation errors and reports them', function (): void {
    [$tenantId, $branch, $user] = createDataUploadContext();

    $csv = implode("\n", [
        'first_name,last_name,middle_name,date_of_birth,gender,phone_number,alternative_phone,email,marital_status,blood_group,occupation,religion,next_of_kin_name,next_of_kin_phone,next_of_kin_relationship',
        'Jane,Doe,,1990-05-15,female,+254712345678,,,,,,,,, ',
        ',Smith,,1985-03-20,male,+254798000002,,,,,,,,, ',
        'Alice,Brown,,not-a-date,female,+254798000003,,,,,,,,, ',
    ]);

    $response = $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->post('/data-upload/patients/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    $result = $response->getSession()->get('import_result');

    expect($result['imported'])->toBe(1);
    expect($result['skipped'])->toBe(2);
    expect($result['errors'])->toHaveCount(2);

    expect(Patient::query()->where('tenant_id', $tenantId)->count())->toBe(1);
});

it('rejects patient import when no active branch is selected', function (): void {
    [, , $user] = createDataUploadContext();

    $csv = implode("\n", [
        'first_name,last_name,middle_name,date_of_birth,gender,phone_number,alternative_phone,email,marital_status,blood_group,occupation,religion,next_of_kin_name,next_of_kin_phone,next_of_kin_relationship',
        'Jane,Doe,,1990-05-15,female,+254712345678,,,,,,,,, ',
    ]);

    $this->actingAs($user)
        ->post('/data-upload/patients/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect()
        ->assertSessionHasErrors('branch');

    expect(Patient::query()->count())->toBe(0);
});

it('skips a row with a duplicate phone number', function (): void {
    [$tenantId, $branch, $user] = createDataUploadContext();

    Patient::query()->create([
        'tenant_id' => $tenantId,
        'patient_number' => 'CGH-PAT-1001',
        'first_name' => 'Existing',
        'last_name' => 'Patient',
        'gender' => 'male',
        'phone_number' => '+254712345678',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $csv = implode("\n", [
        'first_name,last_name,middle_name,date_of_birth,gender,phone_number,alternative_phone,email,marital_status,blood_group,occupation,religion,next_of_kin_name,next_of_kin_phone,next_of_kin_relationship',
        'Jane,Doe,,1990-05-15,female,+254712345678,,,,,,,,, ',
    ]);

    $response = $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->post('/data-upload/patients/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    $result = $response->getSession()->get('import_result');

    expect($result['imported'])->toBe(0);
    expect($result['skipped'])->toBe(1);

    expect(Patient::query()->where('tenant_id', $tenantId)->count())->toBe(1);
});

it('skips duplicate phone numbers within the same uploaded file', function (): void {
    [$tenantId, $branch, $user] = createDataUploadContext();

    $csv = implode("\n", [
        'first_name,last_name,middle_name,date_of_birth,gender,phone_number,alternative_phone,email,marital_status,blood_group,occupation,religion,next_of_kin_name,next_of_kin_phone,next_of_kin_relationship',
        'Jane,Doe,,1990-05-15,female,+254712345678,,,,,,,,, ',
        'Janet,Doe,,1991-05-15,female,+254712345678,,,,,,,,, ',
    ]);

    $response = $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->post('/data-upload/patients/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    $result = $response->getSession()->get('import_result');

    expect($result['imported'])->toBe(1);
    expect($result['skipped'])->toBe(1);
    expect($result['errors'][0]['messages'])->toContain('This phone number appears more than once in the uploaded file.');
    expect(Patient::query()->where('tenant_id', $tenantId)->where('phone_number', '+254712345678')->count())->toBe(1);
});

it('allows duplicate patient emails across different tenants', function (): void {
    [$tenantId, $branch, $user] = createDataUploadContext();
    $otherTenantContext = seedTenantContext();

    Patient::query()->create([
        'tenant_id' => $otherTenantContext['tenant_id'],
        'patient_number' => 'OTHER-PAT-1001',
        'first_name' => 'Other',
        'last_name' => 'Patient',
        'gender' => 'female',
        'phone_number' => '+254700000000',
        'email' => 'shared@example.com',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    $csv = implode("\n", [
        'first_name,last_name,middle_name,date_of_birth,gender,phone_number,alternative_phone,email,marital_status,blood_group,occupation,religion,next_of_kin_name,next_of_kin_phone,next_of_kin_relationship',
        'Jane,Doe,,1990-05-15,female,+254712345678,,shared@example.com,,,,,,, ',
    ]);

    $response = $this->actingAs($user)
        ->withSession([BranchContext::SESSION_KEY => $branch->id])
        ->post('/data-upload/patients/import', ['file' => makeCsvUpload($csv)])
        ->assertRedirect('/data-upload');

    $result = $response->getSession()->get('import_result');

    expect($result['imported'])->toBe(1);
    expect($result['skipped'])->toBe(0);
    expect(Patient::query()->where('tenant_id', $tenantId)->where('email', 'shared@example.com')->exists())->toBeTrue();
});

it('rejects an upload without a file', function (): void {
    [, , $user] = createDataUploadContext();

    $this->actingAs($user)
        ->post('/data-upload/patients/import', [])
        ->assertRedirect()
        ->assertSessionHasErrors('file');
});

it('shows import results from session flash on the index page', function (): void {
    [, , $user] = createDataUploadContext();

    $importResult = [
        'imported' => 3,
        'skipped' => 1,
        'errors' => [
            ['row' => 2, 'name' => 'Row 2', 'messages' => ['The first name field is required.']],
        ],
    ];

    $this->actingAs($user)
        ->withSession(['import_result' => $importResult])
        ->get('/data-upload')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page): AssertableInertia => $page
            ->component('data-upload/index')
            ->where('importResult.imported', 3)
            ->where('importResult.skipped', 1),
        );
});
