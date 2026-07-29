<?php

namespace App\Http\Controllers\Tenant\Pos;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PosRegister;
use App\Services\Pos\PosTerminalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PosPageController extends Controller
{
    public function __invoke(Request $request, PosTerminalService $terminal): View
    {
        $registerId = $request->integer('register_id') ?: null;
        $bootstrap = $terminal->bootstrap($registerId ?: null);

        return view('pos.app', [
            'bootstrap' => $bootstrap,
            'panelDashboardUrl' => url('/app'),
        ]);
    }
}
