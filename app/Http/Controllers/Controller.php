<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Resolve jumlah item per halaman secara aman berdasarkan whitelist.
     */
    protected function resolvePerPage(
        Request $request,
        int $default = 15,
        array $allowed = [10, 12, 15, 20, 25, 50, 100]
    ): int {
        $requested = (int) $request->query('per_page', $default);

        return in_array($requested, $allowed, true) ? $requested : $default;
    }
}
