<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DispensingRecord;
use App\Models\InventoryLocation;
use App\Models\Patient;
use App\Models\PatientVisit;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\InventoryNavigationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class DispensingHistoryController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:pharmacy_dispensing.view', only: ['index', 'export']),
        ];
    }

    public function index(Request $request): Response
    {
        /** @var LengthAwarePaginator<int, DispensingRecord> $records */
        $records = $this->baseQuery($request)
            ->paginate(30)
            ->withQueryString()
            ->through(static fn (DispensingRecord $record): array => [
                'id' => $record->id,
                'dispense_number' => $record->dispense_number,
                'status' => $record->status->value,
                'status_label' => $record->status->label(),
                'dispensed_at' => $record->dispensed_at->toISOString(),
                'visit_number' => $record->visit?->visit_number,
                'patient_name' => $record->visit?->patient !== null
                    ? mb_trim(sprintf('%s %s', $record->visit->patient->first_name, $record->visit->patient->last_name))
                    : null,
                'patient_number' => $record->visit?->patient?->patient_number,
                'inventory_location_name' => $record->inventoryLocation?->name,
                'dispensed_by' => self::dispensedByName($record),
            ]);

        return Inertia::render('pharmacy/dispenses/index', [
            'navigation' => InventoryNavigationContext::fromRequest($request),
            'records' => $records,
            'filters' => [
                'search' => $request->query('search'),
                'status' => $request->query('status'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->baseQuery($request)->with([
            'items.inventoryItem:id,name,generic_name',
            'items.allocations:id,dispensing_record_item_id,batch_number_snapshot',
        ]);

        $filename = sprintf('dispense-history-%s.csv', now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($query): void {
            $handle = fopen('php://output', 'w');
            throw_if($handle === false, RuntimeException::class, 'Unable to open output stream for dispensing export.');

            fputcsv($handle, [
                'Dispense Number',
                'Status',
                'Dispensed At',
                'Visit Number',
                'Patient Name',
                'Patient Number',
                'Location',
                'Dispensed By',
                'Medication',
                'Prescribed Qty',
                'Dispensed Qty',
                'Balance Qty',
                'Item Status',
                'Batch Numbers',
            ],
                escape: '\\');

            $query->each(function (DispensingRecord $record) use ($handle): void {
                foreach ($record->items as $item) {
                    $visit = $record->visit;
                    $patient = $visit?->patient;
                    $inventoryLocation = $record->inventoryLocation;
                    $inventoryItem = $item->inventoryItem;

                    $visitNumber = $visit instanceof PatientVisit
                        ? $visit->visit_number
                        : '';
                    $patientNumber = $patient instanceof Patient
                        ? $patient->patient_number
                        : '';
                    $inventoryLocationName = $inventoryLocation instanceof InventoryLocation
                        ? $inventoryLocation->name
                        : '';
                    $patientName = $patient !== null
                        ? mb_trim(sprintf('%s %s', $patient->first_name, $patient->last_name))
                        : '';

                    $dispensedBy = self::dispensedByName($record) ?? '';

                    $batchNumbers = $item->allocations
                        ->pluck('batch_number_snapshot')
                        ->filter()
                        ->implode('; ');

                    fputcsv($handle, [
                        $record->dispense_number,
                        $record->status->label(),
                        $record->dispensed_at->format('Y-m-d H:i'),
                        $visitNumber,
                        $patientName,
                        $patientNumber,
                        $inventoryLocationName,
                        $dispensedBy,
                        $inventoryItem->generic_name ?? $inventoryItem->name ?? '',
                        number_format((float) $item->prescribed_quantity, 3),
                        number_format((float) $item->dispensed_quantity, 3),
                        number_format((float) $item->balance_quantity, 3),
                        $item->dispense_status->label(),
                        $batchNumbers,
                    ],
                        escape: '\\');
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private static function dispensedByName(DispensingRecord $record): ?string
    {
        $dispensedBy = $record->dispensedBy;

        if ($dispensedBy?->staff !== null) {
            return mb_trim(sprintf('%s %s', $dispensedBy->staff->first_name, $dispensedBy->staff->last_name));
        }

        return $dispensedBy instanceof User ? $dispensedBy->email : null;
    }

    /**
     * @return Builder<DispensingRecord>
     */
    private function baseQuery(Request $request): Builder
    {
        $branchId = BranchContext::getActiveBranchId();

        return DispensingRecord::query()
            ->with([
                'visit.patient:id,first_name,last_name,patient_number',
                'inventoryLocation:id,name',
                'dispensedBy:id,staff_id,email',
                'dispensedBy.staff:id,first_name,last_name',
            ])
            ->when(is_string($branchId) && $branchId !== '', static fn (Builder $q) => $q->where('branch_id', $branchId))
            ->when(
                filled($request->query('search')),
                function (Builder $q) use ($request): void {
                    $search = $request->query('search');
                    $q->where(function (Builder $inner) use ($search): void {
                        $inner->where('dispense_number', 'like', sprintf('%%%s%%', $search))
                            ->orWhereHas('visit', static fn (Builder $v) => $v->where('visit_number', 'like', sprintf('%%%s%%', $search)))
                            ->orWhereHas('visit.patient', static fn (Builder $p) => $p->where('first_name', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('last_name', 'like', sprintf('%%%s%%', $search))
                                ->orWhere('patient_number', 'like', sprintf('%%%s%%', $search)));
                    });
                }
            )
            ->when(filled($request->query('status')), static fn (Builder $q) => $q->where('status', $request->query('status')))
            ->when(filled($request->query('from')), static fn (Builder $q) => $q->whereDate('dispensed_at', '>=', $request->query('from')))
            ->when(filled($request->query('to')), static fn (Builder $q) => $q->whereDate('dispensed_at', '<=', $request->query('to')))
            ->latest('dispensed_at');
    }
}
