# HPK Gudang Inhouse - Project Analysis

## Overview
**HPK Gudang Inhouse** is a custom Warehouse Management System (WMS) built specifically for internal use. The application tracks inventory, manages warehouse locations (specifically "HPK 1-Building Warehouse" with Zones A-F), handles engineering change requests (ECR), material disposals, and QR-based inventory tracking.

## Technology Stack
- **Backend Framework:** Laravel 10 (PHP 8.1+)
- **Frontend:** Laravel Blade, Alpine.js, Tailwind CSS
- **Database:** MySQL (Database name: `hpk_gudang`)
- **Build Tooling:** Vite
- **Key Libraries:** `html5-qrcode` & `qrcode` (for QR scanning and generation capabilities), `simplesoftwareio/simple-qrcode`
- **Authentication & RBAC:** Laravel Breeze & Spatie Laravel Permission (`spatie/laravel-permission`)

## Core Features & Workflows

### 1. User Management, Departments & Roles
Users have specific roles and departmental scopes that dictate their permissions and workflows:
- **Roles:** `admin_gudang`, `operator`, `qc`, `engineering`, `supervisor`, `operator_mesin`, `material_handling`.
- **15 Standardized Departments:** Laser, M1, UNP, Sharing, Band Saw, Aksesoris, Press, Hydraulic, CNC, Sharing & Bending, Gudang, SPO, Robot, Uji 1, Uji 2.
- **Dynamic Access Control:** Users only see navigation menus and dashboard modules that they are authorized to access. Machine center operators default strictly to Work Request, while logistics operators default strictly to Material Handling.

### 2. Location Management (Peta Gudang)
- Manages physical warehouse mapping (specifically handling Zones A through F).
- Tracks locations with high granularity (Aisle, Rack Number, Bin Level) and capacity limits.

### 3. Master Data Komponen (Component Management)
- Manages parts and materials categorized by types (e.g., `hydraulic`, `raw_material`, `fastener`, `accessories`, `electrical`, `chemical_paint`).
- Tracks specifications, Units of Measure (UOM), and minimum/maximum stock thresholds to prevent stockouts.
- Generates and stores QR Code payloads for easy physical tracking and labeling.

### 4. Inventory Transactions (Transaksi Komponen)
- Handles core inventory movements: `inbound`, `outbound`, `transfer`, and `return`.
- Tracks real-time stock balances tied to specific components and their locations within the warehouse.
- Supports linking transactions to SPK (Surat Perintah Kerja) numbers.

### 5. Engineering Change Requests (ECR)
- A structured workflow for proposing and approving changes to components (e.g., `spec_change`, `part_replacement`, `discontinue`).
- Engineers can request changes, define old/new specs, and set stock run-out policies (`run_out`, `immediate_scrap`, `rework`).
- Includes a strict approval workflow (Submitted -> Reviewed by QC -> Approved/Rejected).

### 6. Disposal & Scrap (Pengajuan Disposal / Afkir)
- Manages the process of discarding materials safely and accountably (`scrap_iron`, `damaged_part`, `expired_chemical`, `obsolete`).
- Tracks estimated weight (kg) and expected salvage value.
- Includes a multi-level approval workflow (QC, Manager) and supports attaching photo proofs of the condition.

### 7. QR Label Management (Permintaan Label QR)
- A dedicated queue/module to request and print QR/Barcode labels.
- Supports printing for individual items, boxes, or entire warehouse racks.
- Dedicated routes for different printer types (Thermal printers vs. standard Sheet printers).

### 8. Cycle Counting (Stok Opname)
- Reconciles system database quantities with actual physical quantities in the warehouse.
- Targets specific warehouse zones for organized, periodic counting.
- Tracks variances (System Qty vs. Physical Qty) and requires documented reasons for discrepancies, culminating in an official reconciliation approval process.

### 9. Work Request Inhouse (WRI) & Multi-Step Machine Routing
- Internal inter-departmental request system replacing legacy Google Sheets.
- **Auto-Routing:** Dynamically assigns the next processing machine/station based on component configuration (`process_route`).
- **Hybrid Smart Approval:** Requester operators require Section Head approval (`Menunggu` -> `Diterima`), while Supervisors, Admins, and Urgent Line Stops bypass approval automatically (`Diterima`).
- **Prerequisite Gate:** Receiving departments cannot view or process tickets until requester approval is `Diterima`.
- **Partial Processing (Cicilan):** Supports completing and sending quantities in increments (`Parsial > ...`) with cumulative quota tracking.
- **Three-Point Handover Verification & Defect Correction:** Kuantitas fisik diverifikasi interaktif pada 3 titik serah terima (**Mesin Out &rarr; MH Pickup &rarr; Gudang Terima**). Jika ada komponen cacat atau hilang pada setiap titik, kuota order tiket otomatis dipotong (`quantity_requested` berkurang), dicatat dalam audit trail lengkap (operator, tanggal, jam, alasan), dan saldo pengerjaan dikunci agar tidak melebihi pesanan.
- **Automatic Stock Balance Update:** Once a ticket achieves `Sudah Diambil` status, the physical warehouse rack balance (`stock_balances`) increments automatically (hanya unit bagus) and generates an official inbound `transactions` record.
- **SLA 3-Day Monitoring:** Overdue tickets exceeding 72 hours trigger high-visibility Red Alerts on dashboard cards.

### 10. Material Handling (MH) Command Center
- Dedicated logistics and internal transportation queue for physical movement of components between machine centers and the warehouse.
- **4 Tab Command Center:** `tasksPickup` (items waiting at machine stations), `tasksTransit` (items currently carried by couriers), `tasksDistribution` (items ready for handover), and `tasksHistory` (audit trail of completed transfers).
- **Interactive Verification & Defect Reporting:** Kurir MH dapat memverifikasi jumlah fisik aktual saat pickup serta melaporkan barang hilang/rusak sebelum transit.
- **Live Camera QR Scanning:** Integrates `html5-qrcode` to scan ticket/component barcodes directly from smartphone or tablet cameras for instantaneous verification.
- **Strict Role Gating:** Machine operators can only process their machine stage (`Dalam antrian` -> `Proses (In)` -> `Proses (Out)`), while only Material Handling personnel can pick up and distribute goods (`MH Pickup` -> `Siap Diambil` -> `Menuju Pengaju` -> `Sudah Diambil`).

### 11. Role-Based Access Control (RBAC) & Dynamic Dashboard
- Implemented using Spatie Laravel Permission with granular abilities (`work-request.view`, `work-request.process`, `material-handling.view`, `material-handling.manage`, etc.).
- **Dynamic Beranda & Navigation:** Non-authorized modules are completely omitted from the UI, preventing unauthorized clicks or route access.
- **Intelligent Presets:** New or edited users assigned to Machine Center roles automatically receive Work Request permissions, while Material Handling roles receive logistics permissions.

## Summary
The system is an enterprise-grade, unified Warehouse Management System (WMS) and Work Request (WRI) platform. It seamlessly connects raw material warehouse storage (Zones A-F), multi-station production machine routing (Laser, M1, UNP, CNC, etc.), internal logistics transit (Material Handling), quality engineering control (ECR & Disposal), and inventory reconciliation into a single cohesive, auditable, and QR-driven ecosystem.
