<?php

namespace App\Http\Controllers;

use App\Models\GamePreset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GamePresetController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sport' => ['required', 'in:basketball,volleyball'],
            'rules' => ['required', 'array'],
            'rules.timeouts_full' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rules.timeouts_30s' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rules.timeouts_ot' => ['nullable', 'integer', 'min:0', 'max:5'],
            'rules.period_format' => ['nullable', 'string', 'in:quarters,halves,sets'],
            'rules.period_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'rules.ot_minutes' => ['nullable', 'integer', 'min:1', 'max:30'],
            'rules.shot_clock_seconds' => ['nullable', 'integer', 'min:0', 'max:60'],
            'rules.bonus_foul_threshold' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rules.double_bonus_foul_threshold' => ['nullable', 'integer', 'min:1', 'max:20'],
            'rules.player_foul_limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'rules.timeouts_per_set' => ['nullable', 'integer', 'min:0', 'max:10'],
            'rules.set_target_score' => ['nullable', 'integer', 'min:1', 'max:100'],
            'rules.deciding_set_target_score' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $preset = GamePreset::create([
            'organization_id' => $user->organization_id,
            'user_id' => $user->id,
            'name' => $validated['name'],
            'sport' => $validated['sport'],
            'rules' => $validated['rules'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Preset '{$preset->name}' saved successfully.",
            'preset' => $preset,
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();

        $preset = GamePreset::where('id', $id)
            ->where('organization_id', $user->organization_id)
            ->firstOrFail();

        $name = $preset->name;
        $preset->delete();

        return response()->json([
            'status' => 'success',
            'message' => "Preset '{$name}' deleted.",
            'id' => $id,
        ]);
    }
}
