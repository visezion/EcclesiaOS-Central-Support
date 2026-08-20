<?php

namespace App\Http\Controllers;

use App\Services\UpdateManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class UpdateController
{
    public function page(UpdateManager $updates): View
    {
        return view('system.update', ['update_status' => $updates->status()]);
    }

    public function run(UpdateManager $updates): RedirectResponse
    {
        $result = $updates->request();

        return back()->with($result['accepted'] ? 'status' : 'error', $result['message']);
    }

    public function status(UpdateManager $updates): JsonResponse
    {
        return response()->json($updates->status());
    }
}
