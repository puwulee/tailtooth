<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessBeybladePhoto;
use App\Models\Beyblade;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Authenticity;
use App\Enums\Generation;

/** 參賽者後台：登錄自己的陀螺（含自拍照片，比賽前必須完成）。 */
class BeybladeController extends Controller
{
    public function store(Request $request, Player $player)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'generation' => ['required', new Enum(Generation::class)],
            'authenticity' => ['required', new Enum(Authenticity::class)],
            'parts' => ['nullable', 'array'],
            'photo' => ['required', 'image', 'max:8192'], // 陀螺自拍照
        ]);

        $path = $request->file('photo')->store("beyblades/{$player->id}", 'public');

        $beyblade = Beyblade::create([
            'player_id' => $player->id,
            'name' => $data['name'],
            'generation' => $data['generation'],
            'authenticity' => $data['authenticity'],
            'parts' => $data['parts'] ?? null,
            'photo_path' => $path,
            'photo_status' => 'pending',
        ]);

        // 非同步去背→浮水印→統一尺寸
        ProcessBeybladePhoto::dispatch($beyblade->id);

        return response()->json(['id' => $beyblade->id, 'photo_status' => 'pending'], 201);
    }
}
