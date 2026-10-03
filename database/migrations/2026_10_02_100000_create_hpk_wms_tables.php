<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update users table with role and department
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('operator')->after('email'); // admin_gudang, operator, qc, engineering, supervisor
            $table->string('department')->nullable()->after('role');
            $table->string('phone')->nullable()->after('department');
        });

        // 2. Locations table (Zones A to F in HPK 1-Building Warehouse)
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('zone_code', 10); // A, B, C, D, E, F
            $table->string('zone_name');     // Raw Material Baja, Komponen Hidrolik, dll.
            $table->string('aisle')->nullable();
            $table->string('rack_number')->nullable();
            $table->string('bin_level')->nullable();
            $table->text('description')->nullable();
            $table->integer('max_capacity')->default(100);
            $table->timestamps();
        });

        // 3. Components table
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category'); // hydraulic, raw_material, fastener, accessories, electrical, chemical_paint
            $table->string('uom')->default('Pcs'); // Pcs, Set, Batang (6m), Lembar, Kg, Liter, Box
            $table->text('specification')->nullable();
            $table->integer('minimum_stock')->default(5);
            $table->integer('maximum_stock')->default(100);
            $table->foreignId('default_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->string('image_path')->nullable();
            $table->string('qr_code_payload')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Stock balances
        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained('components')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained('locations')->cascadeOnDelete();
            $table->decimal('quantity', 12, 2)->default(0);
            $table->string('batch_lot_number')->nullable();
            $table->timestamps();
        });

        // 5. Transactions
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->string('type'); // inbound, outbound, transfer, return
            $table->string('spk_number')->nullable(); // Opsional untuk sekarang sesuai permintaan user
            $table->string('reference_document')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->date('transaction_date');
            $table->string('status')->default('completed');
            $table->timestamps();
        });

        // 6. Transaction Items
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('to_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // 7. ECRs (Engineering Change Requests)
        Schema::create('ecrs', function (Blueprint $table) {
            $table->id();
            $table->string('ecr_number')->unique();
            $table->foreignId('component_id')->constrained('components');
            $table->string('title');
            $table->string('revision_type'); // spec_change, part_replacement, discontinue
            $table->text('old_specification')->nullable();
            $table->text('new_specification')->nullable();
            $table->text('reason');
            $table->string('document_path')->nullable();
            $table->string('stock_policy')->default('run_out'); // run_out, immediate_scrap, rework
            $table->string('status')->default('submitted'); // submitted, reviewed_qc, approved, rejected
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // 8. Disposals (Scrap / Afkir Material)
        Schema::create('disposals', function (Blueprint $table) {
            $table->id();
            $table->string('disposal_number')->unique();
            $table->string('disposal_type'); // scrap_iron, damaged_part, expired_chemical, obsolete
            $table->text('reason');
            $table->decimal('estimated_weight_kg', 10, 2)->default(0);
            $table->decimal('estimated_salvage_value', 15, 2)->default(0);
            $table->string('status')->default('submitted'); // submitted, approved_qc, approved_manager, completed, rejected
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('approval_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 9. Disposal Items
        Schema::create('disposal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposal_id')->constrained('disposals')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components');
            $table->foreignId('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->decimal('quantity', 12, 2);
            $table->string('condition_description')->nullable();
            $table->string('proof_photo_path')->nullable();
            $table->timestamps();
        });

        // 10. QR Requests
        Schema::create('qr_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('component_id')->constrained('components');
            $table->string('label_type')->default('item'); // item, box, rack
            $table->integer('print_qty')->default(1);
            $table->string('notes')->nullable();
            $table->string('status')->default('pending'); // pending, printed
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });

        // 11. Cycle Counts
        Schema::create('cycle_counts', function (Blueprint $table) {
            $table->id();
            $table->string('count_number')->unique();
            $table->string('zone_target'); // A, B, C, D, E, F
            $table->date('count_date');
            $table->text('notes')->nullable();
            $table->string('status')->default('in_progress'); // in_progress, pending_review, reconciled
            $table->foreignId('conducted_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();
        });

        // 12. Cycle Count Items
        Schema::create('cycle_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cycle_count_id')->constrained('cycle_counts')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('components');
            $table->foreignId('location_id')->constrained('locations');
            $table->decimal('system_qty', 12, 2)->default(0);
            $table->decimal('physical_qty', 12, 2)->default(0);
            $table->decimal('variance_qty', 12, 2)->default(0);
            $table->text('variance_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cycle_count_items');
        Schema::dropIfExists('cycle_counts');
        Schema::dropIfExists('qr_requests');
        Schema::dropIfExists('disposal_items');
        Schema::dropIfExists('disposals');
        Schema::dropIfExists('ecrs');
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('stock_balances');
        Schema::dropIfExists('components');
        Schema::dropIfExists('locations');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'department', 'phone']);
        });
    }
};
