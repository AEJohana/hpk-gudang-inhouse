<?php

namespace Tests\Unit;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransactionSlipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('department')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });
    }

    /**
     * Test transaction slip view renders anti-collision styling and proper print CSS.
     */
    public function test_transaction_slip_view_renders_anti_collision_and_print_styling(): void
    {
        $role = Role::create(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $user = User::create([
            'name' => 'Pak Panji',
            'email' => 'panji@hpk.test',
            'password' => bcrypt('secret'),
        ]);
        $user->assignRole($role);

        $mockComponent = (object) [
            'part_number' => 'SDTPBRDD3000000',
            'name' => 'BANTALAN BODY KUPU KUPU',
            'uom' => 'PCS',
            'image_url' => 'https://via.placeholder.com/150',
        ];

        $mockFromLocation = (object) [
            'full_location_code' => 'Gudang Luar / Vendor',
        ];

        $mockToLocation = (object) [
            'full_location_code' => '1-R7-L4-03',
        ];

        $mockItem = (object) [
            'component' => $mockComponent,
            'quantity' => 10,
            'fromLocation' => $mockFromLocation,
            'toLocation' => $mockToLocation,
        ];

        $mockTransaction = (object) [
            'transaction_number' => 'TRX/IN-WRI/20261103/ICKXA',
            'type' => 'inbound',
            'transaction_date' => Carbon::create(2026, 11, 3),
            'type_badge' => [
                'label' => 'Penerimaan (Inbound)',
                'icon' => 'fa-truck-ramp-box',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            ],
            'spk_number' => null,
            'reference_document' => 'WRI/DeptGudang/20261102/1/SDTPBRDD3000000',
            'user' => $user,
            'notes' => 'Serah terima WRI #WRI/DeptGudang/20261102/1/SDTPBRDD3000000 ke rak gudang 1-R7-L4-03',
            'items' => collect([$mockItem]),
        ];

        $this->actingAs($user);

        $renderedHtml = view('transactions.show', [
            'transaction' => $mockTransaction,
        ])->render();

        // 1. Assert Anti-Collision styling is present for reference_document
        $this->assertStringContainsString('break-all', $renderedHtml);
        $this->assertStringContainsString('min-w-0', $renderedHtml);
        $this->assertStringContainsString('WRI/DeptGudang/20261102/1/SDTPBRDD3000000', $renderedHtml);
        $this->assertStringContainsString('Pak Panji', $renderedHtml);

        // 2. Assert Notes break-words
        $this->assertStringContainsString('break-words', $renderedHtml);

        // 3. Assert Print Stylesheet and Page setup
        $this->assertStringContainsString('@page', $renderedHtml);
        $this->assertStringContainsString('size: A4 portrait;', $renderedHtml);
        $this->assertStringContainsString('margin: 12mm 15mm;', $renderedHtml);
        $this->assertStringContainsString('#printable-slip', $renderedHtml);
        $this->assertStringContainsString('print:grid-cols-4', $renderedHtml);
        $this->assertStringContainsString('print:grid-cols-2', $renderedHtml);

        // 4. Assert QC column is removed and receiver signature format is customized
        $this->assertStringNotContainsString('QC Inspeksi', $renderedHtml);
        $this->assertStringContainsString('Diterima Oleh (Gudang):', $renderedHtml);
        $this->assertStringContainsString('( Nama Terang &amp; TTD )', $renderedHtml);

        // 5. Assert Background Grid is removed
        $this->assertStringContainsString('background-image: none !important;', $renderedHtml);
    }

    /**
     * Test bulk print sheet view chunks 2 slips per A4 page to save paper.
     */
    public function test_bulk_print_sheet_view_chunks_two_slips_per_a4_page(): void
    {
        $role = Role::firstOrCreate(['name' => 'admin_gudang', 'guard_name' => 'web']);
        $user = User::firstOrCreate(
            ['email' => 'panji@hpk.test'],
            ['name' => 'Pak Panji', 'password' => bcrypt('secret')]
        );

        $mockComponent = (object) [
            'part_number' => 'PART-A-001',
            'name' => 'KOMPONEN KAROSERI A',
            'uom' => 'PCS',
            'image_url' => 'https://via.placeholder.com/150',
        ];

        $mockItem = (object) [
            'component' => $mockComponent,
            'quantity' => 5,
            'fromLocation' => (object) ['full_location_code' => 'Gudang'],
            'toLocation' => (object) ['full_location_code' => 'Lini'],
        ];

        $tx1 = (object) [
            'id' => 101,
            'transaction_number' => 'TRX/IN/001',
            'type' => 'inbound',
            'transaction_date' => Carbon::now(),
            'type_badge' => ['label' => 'Inbound', 'class' => 'bg-emerald-100'],
            'spk_number' => 'SPK-001',
            'reference_document' => 'DOC-REF-001',
            'user' => $user,
            'notes' => 'Catatan 1',
            'items' => collect([$mockItem]),
        ];

        $tx2 = (object) [
            'id' => 102,
            'transaction_number' => 'TRX/IN/002',
            'type' => 'outbound',
            'transaction_date' => Carbon::now(),
            'type_badge' => ['label' => 'Outbound', 'class' => 'bg-blue-100'],
            'spk_number' => 'SPK-002',
            'reference_document' => 'DOC-REF-002',
            'user' => $user,
            'notes' => 'Catatan 2',
            'items' => collect([$mockItem]),
        ];

        $renderedHtml = view('transactions.print_sheet', [
            'transactions' => collect([$tx1, $tx2]),
        ])->render();

        $this->assertStringContainsString('TRX/IN/001', $renderedHtml);
        $this->assertStringContainsString('TRX/IN/002', $renderedHtml);
        $this->assertStringContainsString('✂ POTONG DI SINI / TEAR HERE', $renderedHtml);
        $this->assertStringContainsString('Hemat Kertas: Otomatis 2 Slip per 1 Lembar A4', $renderedHtml);
        $this->assertStringContainsString('Diterima Oleh (Gudang):', $renderedHtml);
        $this->assertStringContainsString('( Nama Terang &amp; TTD )', $renderedHtml);
        $this->assertStringNotContainsString('QC Inspeksi', $renderedHtml);
        $this->assertStringContainsString('background-image: none !important;', $renderedHtml);
    }
}
