<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TipController extends Controller
{
    /**
     * Envia coins (tip) de un usuario a otro. Transfiere el saldo con
     * bloqueo y genera una notificacion al receptor.
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $tipper = $request->user();

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);
        $amount = (int) $validated['amount'];

        if ($user->id === $tipper->id) {
            return response()->json(['message' => 'No puedes enviarte un tip a ti mismo.'], 422);
        }

        if ($tipper->coins < $amount) {
            return response()->json([
                'message' => 'No tienes suficientes coins.',
                'your_coins' => $tipper->coins,
                'amount' => $amount,
            ], 402);
        }

        DB::transaction(function () use ($tipper, $user, $amount): void {
            $tipper = User::whereKey($tipper->id)->lockForUpdate()->firstOrFail();
            $receiver = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            $tipper->decrement('coins', $amount);
            $receiver->increment('coins', $amount);
        });

        AppNotification::notify($user, $tipper, AppNotification::TYPE_TIP);

        return response()->json([
            'status' => 'success',
            'message' => "Enviaste {$amount} coins a @{$user->username}.",
            'data' => [
                'amount' => $amount,
                'receiver_id' => $user->id,
                'coins_remaining' => $tipper->fresh()->coins,
            ],
        ], 201);
    }
}
