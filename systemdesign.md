# Hospital Management System (HMS) - System Design & UI Architecture

## System Overview
This is a modern, multi-tenant Hospital Management System (HMS) built with Laravel, Inertia.js, and React. It is designed to handle the end-to-end lifecycle of patient care, from registration and triage to consultation, laboratory investigations, pharmacy dispensing, and billing.

### Core Modules & Detailed Capabilities

#### 1. Patient Management & EMR
*   **Patient Profiles:** Captures personal details, allergens, next of kin, and address history.
*   **Visit Tracking:** Unique `visit_number`, `is_emergency` flag, and transitionary statuses (Registered -> Started -> Completed).
*   **Payer Management:** visit-level `VisitPayer` (Cash vs. Insurance) which determines price resolution for all orders.

#### 2. Clinical Workflow
*   **Triage:** Captures comprehensive vital signs (BP, Pulse, Temp, Weight, Height, BMI, SpO2, Resp Rate) and nurse initial assessments.
*   **Consultation:** SOAP-based clinician notes linked to ICD diagnoses and multiple order types.
*   **Orders:** Unified ordering system for Laboratory, Imaging, Prescriptions, and General Facility Services.

#### 3. Laboratory Information System (LIS)
*   **Catalog Management:** Tests categorized by department with `base_price` and required `specimen_types`.
*   **Result Capture Types:** Supports multiple capture methods:
    *   **Numeric:** Single value with reference ranges (Low/High/Normal).
    *   **Options:** Dropdown-based result selection (e.g., Positive/Negative).
    *   **Parameters:** Multi-component tests with unique sub-parameters.
*   **Workflow:** Multi-stage tracking: Specimen Collection -> Receiving -> Result Entry -> Review -> Approval.
*   **Consumables:** Tracking inventory items (e.g., reagents, tubes) used during test execution.

#### 4. Pharmacy & Point of Sale (POS)
*   **Prescription Management:** Status-tracked items (Pending -> Dispensed -> Cancelled) with batch-specific allocation.
*   **Retail POS:**
    *   **Carts:** Active sessions with `gross_amount`, `discount_amount`, and `paid_amount`.
    *   **Sales History:** Tracking `PharmacyPosSale` with statuses (Draft -> Paid -> Voided -> Refunded).
    *   **Payment Integration:** Support for multiple payment methods and change calculation.

#### 5. Inventory & Supply Chain
*   **Item Classification:** Categorized as `Drug`, `Consumable`, `Supply`, `Reagent`, or `Other`.
*   **Drug Specifics:** Tracking `DrugCategory`, `DrugDosageForm`, and `therapeutic_classes`.
*   **Stock Management:**
    *   **Batches:** Tracking `batch_number`, `expiry_date`, and `cost_price` per batch.
    *   **Locations:** Multi-location stock tracking (Main Store, Pharmacy, Lab, Theatre).
    *   **Movements:** Comprehensive ledger for every stock-in/stock-out event.
*   **Procurement:** Workflow from Purchase Order -> Goods Receipt -> Stock Requisition -> Issuance.

#### 6. Revenue & Billing
*   **Tariffs:** Dynamic price resolution based on the visit's payer (Insurance co-pay vs. Cash full price).
*   **Visit Billing:** Unified view of all charges (Labs, Pharmacy, Consultation, Services) vs. payments received.
*   **Invoicing:** Generation of professional invoices for patients and insurance companies.

---

## Suggested Pages & UI Templates

### 1. Patient Registration & Onboarding
*   **Objective:** Quick entry with duplicate prevention and insurance verification.
*   **Key Components:** Stepper Form, Avatar Upload, Insurance Package Selector.

### 2. Triage & Vital Signs
*   **Objective:** Assessment of patient state with visual alerts for abnormal vitals.
*   **Key Components:** Vitals Grid, BMI Calculator, Urgency Triage Color-Coding.

### 3. Doctor's Consultation Room
*   **Objective:** Central clinical hub for SOAP notes and rapid ordering.
*   **Key Components:** SOAP Editor, Clinical Timeline, Diagnosis Search, Order Sidebar.

### 4. Patient Visit Profile (360° View)
*   **Objective:** Real-time summary of the current visit journey and costs.
*   **Key Components:** Journey Progress Banner, Billing Summary Counter, Activity Feed.

### 5. Longitudinal Patient Profile (Lifetime EMR)
*   **Objective:** Historical medical record across all previous visits.
*   **Key Components:** Lifetime Timeline, Biometric Sparklines, Document/Imaging Vault.

### 6. Nursing & Care Workspace
*   **Objective:** Bedside monitoring and medication administration.
*   **Key Components:** MAR (Medication Administration Record), Observation Charts (NEWS/PEWS), Handover Notes.

### 7. Laboratory Worklist & Result Entry
*   **Objective:** High-efficiency interface for sample processing and result entry.
*   **Key Components:** Batch Result Spreadsheet, Reference Range Highlighting, Status Tabs.

### 8. Pharmacy POS & Dispensing
*   **Objective:** Fast checkout for both prescriptions and walk-in customers.
*   **Key Components:** Batch Selector, Cart Manager, Payment Modal, Receipt Printer Layout.

### 9. Inventory & Procurement Dashboard
*   **Objective:** Stock health monitoring and supply chain automation.
*   **Key Components:** Low Stock Alerts, Stock Movement Ledger, Requisition Approval Flow.

---

## Technical Specifications for AI Prompting
*   **Framework:** React (TypeScript) with Inertia.js.
*   **Styling:** Tailwind CSS + shadcn/ui.
*   **Icons:** Lucide-react.
*   **State Management:** Inertia `useForm`, `usePage`, and React context where needed.
*   **Validation:** Zod schemas matching Laravel backend rules.
