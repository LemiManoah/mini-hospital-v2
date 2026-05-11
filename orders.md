# Orders System Overview

This document describes how clinical orders work in mini-hospital-v2, covering lab orders, prescriptions, imaging orders, and facility service orders.

## Order Types

### 1. Lab Orders

**Models:**
- `App\Models\LabOrder` - Parent order entity
- `App\Models\LabOrderItem` - Individual test items
- `App\Models\LabSpecimen` - Sample tracking
- `App\Models\LabResultEntry` - Test results

**Creation Flow:**
- Doctor creates lab order during consultation via `VisitOrderController::storeLabRequest`
- `CreateLabOrder` action validates tests, creates LabOrder with LabOrderItems
- Each item linked to a test from `LabTestCatalog`
- Billing synced via `SyncLabOrderCharge` action
- Visit status transitions from REGISTERED to IN_PROGRESS

**Status Lifecycle:**

```
LabOrderStatus:
  REQUESTED → SAMPLE_COLLECTED → IN_PROGRESS → COMPLETED
       ↓                    ↓
   CANCELLED           REJECTED
```

```
LabOrderItemStatus (per item):
  PENDING → IN_PROGRESS → COMPLETED
        ↓
     CANCELLED
```

**Workflow Stages (computed on LabOrderItem):**
```
pending → sample_collected → result_entered → reviewed → approved
         (received)        (results saved)  (reviewed)   (verified)
```

**Key Actions:**
- `CreateLabOrder` - Creates new lab order
- `ReceiveLabOrderItem` - Marks specimen as received in lab
- `CollectLabSpecimen` - Records specimen collection with status (COLLECTED/REJECTED)
- `StoreLabResultEntry` - Saves test results
- `ReviewLabResultEntry` - Lab technician reviews results
- `ApproveLabResultEntry` - pathologist/authorized staff approves final results
- `SyncLabOrderProgress` - Auto-updates parent order status based on item states

**Billing:**
- `LabBillingStatus`: PENDING → BILLED → PAID/INSURANCE
- Prices stored on LabOrderItem from LabTestCatalog::base_price

---

### 2. Prescriptions

**Models:**
- `App\Models\Prescription` - Prescription header
- `App\Models\PrescriptionItem` - Individual prescribed items
- `App\Models\DispensingRecord` - Pharmacy dispensing event
- `App\Models\DispensingRecordItem` - Dispensed item details

**Status:**
- `PrescriptionStatus`: PENDING, etc.
- `PrescriptionItemStatus`: PENDING, DISPENSED, etc.

**Flow:**
- Created during doctor consultation via `VisitOrderController::storePrescription`
- Items reference `InventoryItem` (drugs)
- Pharmacist processes via pharmacy queue
- Dispensing updates stock/inventory

---

### 3. Imaging Orders

- Created via `VisitOrderController::storeImagingRequest`
- Processed through imaging queue (similar to lab workflow)
- Status tracking for order lifecycle

---

### 4. Facility Service Orders

**Model:** `App\Models\FacilityServiceOrder`

- Created via `VisitOrderController::storeFacilityServiceOrder`
- Non-pharmacy clinical orders (e.g., physiotherapy, nursing services)
- Status: DRAFT → POSTED → COMPLETED/CANCELLED

---

## Key Controllers

| Controller | Responsibility |
|------------|----------------|
| `VisitOrderController` | Creates all order types during consultation |
| `LaboratoryQueueController` | Processes lab orders (receive, enter results, review) |
| `PharmacyQueueController` | Processes prescriptions/dispensing |
| `ImagingQueueController` | Processes imaging orders |

---

## Key Patterns

1. **Order Creation**: All orders created during patient visit/consultation
2. **Queue Processing**: After creation, orders move to department queues for processing
3. **Status Sync**: Parent order status computed from child items (see SyncLabOrderProgress)
4. **Billing Integration**: Orders linked to billing system via billing_status fields
5. **Audit Trail**: Actions record audit logs (see RecordAuditActivity in CreateLabOrder)
6. **Notifications**: Users with relevant permissions notified of new orders

---

## Related Enums

- `App\Enums\LabOrderStatus`
- `App\Enums\LabOrderItemStatus`
- `App\Enums\LabSpecimenStatus`
- `App\Enums\LabBillingStatus`
- `App\Enums\PrescriptionStatus`
- `App\Enums\PrescriptionItemStatus`
- `App\Enums\Priority` (for order priority: ROUTINE, URGENT, STAT)