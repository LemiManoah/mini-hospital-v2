<?php

declare(strict_types=1);

namespace App\Http\Controllers\Print;

use App\Models\LabOrderItem;
use App\Support\ActiveBranchWorkspace;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

final readonly class LabResultPrintController implements HasMiddleware
{
    public function __construct(
        private ActiveBranchWorkspace $activeBranchWorkspace,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:lab_orders.view', only: ['show']),
        ];
    }

    public function show(LabOrderItem $labOrderItem): Response
    {
        $labOrder = $labOrderItem->order()->firstOrFail();
        $this->activeBranchWorkspace->authorizeModel($labOrder);

        abort_unless(
            $labOrderItem->result_visible,
            403,
            'Only released laboratory results can be printed.',
        );

        $labOrderItem->loadMissing([
            'test:id,test_code,test_name',
            'specimen:id,lab_order_item_id,accession_number,specimen_type_name,collected_at,outside_sample,outside_sample_origin',
            'order:id,visit_id,requested_by,request_date,priority,clinical_notes,facility_branch_id',
            'order.requestedBy:id,first_name,last_name',
            'order.visit:id,visit_number,patient_id,facility_branch_id',
            'order.visit.patient:id,patient_number,first_name,last_name,middle_name,date_of_birth,age,age_units,gender,phone_number',
            'order.visit.branch:id,name,branch_code',
            'resultEntry:id,lab_order_item_id,entered_at,reviewed_at,approved_at,released_by,released_at,result_notes,review_notes,approval_notes,approved_by',
            'resultEntry.approvedBy:id,first_name,last_name',
            'resultEntry.releasedBy:id,first_name,last_name',
            'resultEntry.values:id,lab_result_entry_id,lab_test_result_parameter_id,label,value_numeric,value_text,unit,gender,age_min,age_max,reference_range,sort_order',
        ]);

        $visit = $labOrderItem->order?->visit;
        $patient = $visit?->patient;
        $test = $labOrderItem->test;
        $visitNumber = $visit !== null ? $visit->visit_number : 'visit';
        $testName = $test !== null ? $test->test_name : 'result';
        $filename = sprintf(
            'lab-result-%s-%s.pdf',
            $visitNumber,
            Str::slug($testName),
        );

        $pdf = Pdf::loadView('print.lab-result', [
            'labOrderItem' => $labOrderItem,
            'labOrder' => $labOrder,
            'visit' => $visit,
            'patient' => $patient,
            'test' => $test,
            'printedAt' => now(),
            'printedBy' => request()->user(),
        ])->setPaper('a4');

        return $pdf->stream($filename);
    }
}
