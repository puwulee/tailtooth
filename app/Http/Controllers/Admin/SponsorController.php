<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sponsor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** 贊助商管理：等級、Logo（露出於首頁/看板/直播版面）。 */
class SponsorController extends Controller
{
    public function ui()
    {
        return view('admin-sponsors');
    }

    public function index(): JsonResponse
    {
        return response()->json(Sponsor::latest()->get()->map(fn ($s) => [
            'id' => $s->id, 'name' => $s->name, 'tier' => $s->tier,
            'logo' => $s->logo_path ? asset('storage/' . $s->logo_path) : null,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'tier' => ['nullable', 'string', 'max:20'],
            'logo' => ['nullable', 'image', 'max:4096'],
        ]);
        $path = $request->hasFile('logo') ? $request->file('logo')->store('sponsors', 'public') : null;
        $sponsor = Sponsor::create(['name' => $data['name'], 'tier' => $data['tier'] ?? null, 'logo_path' => $path]);

        return response()->json(['id' => $sponsor->id], 201);
    }

    public function destroy(Sponsor $sponsor): JsonResponse
    {
        $sponsor->delete();

        return response()->json(['ok' => true]);
    }
}
