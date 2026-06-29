<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Services\PlayerPhotoService;
use Illuminate\Http\Request;

/** 參賽者後台：上傳大頭照（人像，置中裁切統一尺寸）。 */
class ProfileController extends Controller
{
    public function uploadAvatar(Request $request, Player $player, PlayerPhotoService $service)
    {
        $request->validate(['avatar' => ['required', 'image', 'max:8192']]);

        $path = $request->file('avatar')->store("avatars/{$player->id}", 'public');
        $player->update(['avatar_path' => $path]);
        $service->processAvatar($player);

        return response()->json(['avatar_path' => $player->fresh()->avatar_path]);
    }
}
