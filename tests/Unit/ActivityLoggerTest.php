<?php

namespace Tests\Unit;

use App\Models\Component;
use App\Models\User;
use App\Models\WorkRequest;
use App\Services\ActivityLogger;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('activity_log');
        Schema::dropIfExists('work_requests');
        Schema::dropIfExists('components');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('department')->nullable();
            $table->timestamps();
        });

        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->string('category')->default('body');
            $table->timestamps();
        });

        Schema::create('work_requests', function (Blueprint $table) {
            $table->id();
            $table->string('wri_number')->unique();
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('properties')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
    }

    public function test_activity_logger_writes_entry_with_causer_and_subject(): void
    {
        $user = User::create([
            'name' => 'Budi Santoso',
            'email' => 'budi@hpk.test',
            'password' => bcrypt('password'),
            'department' => 'Laser',
        ]);

        $comp = Component::create([
            'part_number' => 'CSBA10320130000',
            'name' => 'Bracket Assembly',
        ]);

        ActivityLogger::component(
            'created',
            "Menambahkan master komponen baru: {$comp->name} [{$comp->part_number}].",
            $comp,
            ['part_number' => $comp->part_number],
            $user
        );

        $log = Activity::latest('id')->first();

        $this->assertNotNull($log);
        $this->assertEquals('component', $log->log_name);
        $this->assertEquals('created', $log->event);
        $this->assertStringContainsString('CSBA10320130000', $log->description);
        $this->assertEquals($user->id, $log->causer_id);
        $this->assertEquals($comp->id, $log->subject_id);
        $this->assertEquals(Component::class, $log->subject_type);
        $this->assertEquals('CSBA10320130000', $log->properties['part_number'] ?? null);
    }

    public function test_activity_logger_auth_helper(): void
    {
        $user = User::create([
            'name' => 'Agus Priyono',
            'email' => 'agus@hpk.test',
            'password' => bcrypt('password'),
            'department' => 'Gudang',
        ]);

        ActivityLogger::auth(
            'login',
            "Pengguna {$user->name} berhasil masuk ke sistem.",
            $user,
            ['email' => $user->email]
        );

        $log = Activity::latest('id')->first();

        $this->assertNotNull($log);
        $this->assertEquals('auth', $log->log_name);
        $this->assertEquals('login', $log->event);
        $this->assertEquals($user->id, $log->causer_id);
        $this->assertEquals('agus@hpk.test', $log->properties['email'] ?? null);
    }

    public function test_kpi_metrics_and_module_aggregation(): void
    {
        $user = User::create([
            'name' => 'Operator 1',
            'email' => 'op1@hpk.test',
            'password' => bcrypt('password'),
        ]);

        // 3 normal activities today
        ActivityLogger::workRequest('created', 'Tiket 1 dibuat', null, [], $user);
        ActivityLogger::workRequest('approved', 'Tiket 1 diapprove', null, [], $user);
        ActivityLogger::transaction('inbound', 'Inbound material', null, [], $user);

        // 1 critical defect activity today
        ActivityLogger::workRequest('defect', 'Defect dilaporkan', null, [], $user);

        $today = Carbon::today();
        $totalToday = Activity::whereDate('created_at', $today)->count();
        $usersActiveToday = Activity::whereDate('created_at', $today)->distinct('causer_id')->count('causer_id');
        $criticalToday = Activity::whereDate('created_at', $today)->whereIn('event', ['defect', 'reject', 'deleted'])->count();

        $this->assertEquals(4, $totalToday);
        $this->assertEquals(1, $usersActiveToday);
        $this->assertEquals(1, $criticalToday);

        $topModule = Activity::selectRaw('log_name, COUNT(*) as cnt')
            ->groupBy('log_name')
            ->orderByDesc('cnt')
            ->first();

        $this->assertEquals('work_request', $topModule->log_name);
        $this->assertEquals(3, $topModule->cnt);
    }

    public function test_activity_filtering_by_module_and_search(): void
    {
        $userA = User::create([
            'name' => 'User Alpha',
            'email' => 'alpha@hpk.test',
            'password' => bcrypt('password'),
        ]);

        $userB = User::create([
            'name' => 'User Beta',
            'email' => 'beta@hpk.test',
            'password' => bcrypt('password'),
        ]);

        ActivityLogger::workRequest('process', 'Proses potong laser WRI-001', null, [], $userA);
        ActivityLogger::qr('printed', 'Cetak label QR thermal RACK-A1', null, [], $userB);

        // Filter by module: qr_label
        $qrLogs = Activity::where('log_name', 'qr_label')->get();
        $this->assertCount(1, $qrLogs);
        $this->assertEquals('printed', $qrLogs->first()->event);

        // Filter by causer_id
        $userALogs = Activity::where('causer_id', $userA->id)->get();
        $this->assertCount(1, $userALogs);
        $this->assertEquals('work_request', $userALogs->first()->log_name);

        // Search text
        $searchResults = Activity::where(function ($q) {
            $q->where('description', 'like', '%thermal%');
        })->get();
        $this->assertCount(1, $searchResults);
    }
}
