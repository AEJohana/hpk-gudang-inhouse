# HPK Gudang Inhouse - Project Analysis

## Overview
**HPK Gudang Inhouse** is a custom Warehouse Management System (WMS) built specifically for internal use. The application tracks inventory, manages warehouse locations (specifically "HPK 1-Building Warehouse" with Zones A-F), handles engineering change requests (ECR), material disposals, and QR-based inventory tracking.

## Technology Stack
- **Backend Framework:** Laravel 10 (PHP 8.1+)
- **Frontend:** Laravel Blade, Alpine.js, Tailwind CSS
- **Database:** MySQL (Database name: `hpk_gudang`)
- **Build Tooling:** Vite
- **Key Libraries:** `html5-qrcode` & `qrcode` (for QR scanning and generation capabilities)
- **Authentication:** Laravel Breeze

## Core Features & Workflows

### 1. User Management & Roles
Users have specific roles that dictate their permissions and workflows within the warehouse:
- **Roles:** `admin_gudang`, `operator`, `qc`, `engineering`, `supervisor`.

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

## Summary
The system is a comprehensive, highly tailored WMS. It goes beyond simple stock tracking by deeply integrating quality control, engineering change workflows (ECR), and strict disposal management directly into the warehouse operations lifecycle. It is heavily reliant on QR code scanning to bridge the gap between physical items and digital records.
