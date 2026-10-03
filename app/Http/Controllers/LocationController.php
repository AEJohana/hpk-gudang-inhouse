<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $zones = [
            'A' => [
                'code' => 'A',
                'name' => 'Raw Material Baja',
                'description' => 'Pelat Hitam, Bordes, Profil Baja UNP, WF, Pipa (Area Crane)',
                'theme' => 'blue',
                'locations' => Location::where('zone_code', 'A')->with('stockBalances.component')->get(),
            ],
            'B' => [
                'code' => 'B',
                'name' => 'Komponen Hidrolik & Presisi',
                'description' => 'Silinder Telescopic, Pompa Hidrolik, Control Valve, PTO Transmisi',
                'theme' => 'indigo',
                'locations' => Location::where('zone_code', 'B')->with('stockBalances.component')->get(),
            ],
            'C' => [
                'code' => 'C',
                'name' => 'Hardware, Fastener & Aksesoris',
                'description' => 'Multi-tier Bins Baut Grade 8.8, Engsel Dump, Twist Lock, Lampu LED',
                'theme' => 'emerald',
                'locations' => Location::where('zone_code', 'C')->with('stockBalances.component')->get(),
            ],
            'D' => [
                'code' => 'D',
                'name' => 'Chemical & Cat',
                'description' => 'Ruang Berventilasi Khusus Drum Cat PU, Epoxy Primer, Thinner',
                'theme' => 'amber',
                'locations' => Location::where('zone_code', 'D')->with('stockBalances.component')->get(),
            ],
            'E' => [
                'code' => 'E',
                'name' => 'Buffer Staging Lini Perakitan',
                'description' => 'Area Transit Material Siap Ambil Divisi Assembly Karoseri',
                'theme' => 'cyan',
                'locations' => Location::where('zone_code', 'E')->with('stockBalances.component')->get(),
            ],
            'F' => [
                'code' => 'F',
                'name' => 'Karantina & Scrap Yard',
                'description' => 'Penampungan Afkir & Potongan Pelat Besi Tua Sebelum Disposal',
                'theme' => 'rose',
                'locations' => Location::where('zone_code', 'F')->with('stockBalances.component')->get(),
            ],
        ];

        return view('locations.index', compact('zones'));
    }

    public function show(Location $location)
    {
        $location->load(['stockBalances.component']);
        return view('locations.show', compact('location'));
    }
}
