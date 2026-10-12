<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyCheckinController extends Controller
{
    /** Recompensa base del dia 1. */
    private const BASE_REWARD = 10;

    /** Coins extra por cada dia adicional de racha. */
    private const STREAK_BONUS = 2;

    /** Tope maximo de recompensa diaria. */
    private const MAX_REWARD = 30;

    /**
     * Estado del check-in del usuario (para pintar el boton en Home).
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $claimedToday = $this->claimedToday($user);

        $nextReward = $claimedToday
            ? 0
            : self::rewardForStreak($this->computeStreak($user));

        return response()->json([
            'claimed_today' => $claimedToday,
            'streak' => $user->checkin_streak,
            'next_reward' => $nextReward,
            'coins' => $user->coins,
        ]);
    }

    /**
     * Reclama la recompensa diaria. Incrementa la racha y otorga coins.
     */
    public function claim(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($this->claimedToday($user)) {
            return response()->json([
                'message' => 'Ya reclamaste la recompensa de hoy.',
                'claimed_today' => true,
            ], 409);
        }

        $streak = $this->computeStreak($user);
        $reward = self::rewardForStreak($streak);

        $user->forceFill([
            'checkin_streak' => $streak,
            'last_checkin_at' => now()->toDateString(),
        ])->save();
        $user->increment('coins', $reward);

        return response()->json([
            'status' => 'success',
            'message' => "Recompensa reclamada (+{$reward} coins).",
            'data' => [
                'reward' => $reward,
                'streak' => $streak,
                'coins' => $user->fresh()->coins,
                'claimed_today' => true,
            ],
        ], 201);
    }

    /**
     * Recompensa para una racha dada: base + bonus por dia, con tope.
     */
    public static function rewardForStreak(int $streak): int
    {
        return min(self::BASE_REWARD + max(0, $streak - 1) * self::STREAK_BONUS, self::MAX_REWARD);
    }

    private function claimedToday(User $user): bool
    {
        return $user->last_checkin_at !== null
            && $user->last_checkin_at->toDateString() === now()->toDateString();
    }

    /**
     * Racha que resultaria de reclamar hoy (teniendo en cuenta si ayer hubo check-in).
     */
    private function computeStreak(User $user): int
    {
        if ($user->last_checkin_at === null) {
            return 1;
        }

        $last = $user->last_checkin_at->toDateString();

        if ($last === now()->toDateString()) {
            return $user->checkin_streak;
        }

        if ($last === now()->subDay()->toDateString()) {
            return $user->checkin_streak + 1;
        }

        return 1;
    }
}
