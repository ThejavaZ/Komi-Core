<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\StoreItemResource;
use App\Http\Resources\UserResource;
use App\Models\StoreItem;
use App\Models\StorePurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    /**
     * Catalogo publico de items activos.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 20), 50);

        $items = StoreItem::query()
            ->where('status', StoreItem::STATUS_ACTIVE)
            ->with('seller:id,name,username,avatar')
            ->latest()
            ->paginate($perPage);

        return StoreItemResource::collection($items);
    }

    /**
     * Items que yo he publicado (como vendedor).
     */
    public function mine(Request $request): AnonymousResourceCollection
    {
        $perPage = min((int) $request->input('per_page', 20), 50);

        $items = StoreItem::query()
            ->where('seller_id', $request->user()->id)
            ->with('seller:id,name,username,avatar')
            ->latest()
            ->paginate($perPage);

        return StoreItemResource::collection($items);
    }

    /**
     * Crear un item en la tienda (el usuario autenticado es el vendedor).
     */
    public function store(StoreItemRequest $request): JsonResponse|StoreItemResource
    {
        $item = $request->user()->storeItems()->create($request->validated());

        $item->load('seller:id,name,username,avatar');

        return (new StoreItemResource($item))->response()->setStatusCode(201);
    }

    /**
     * Comprar un item: valida saldo, transfiere coins vendedor<->comprador
     * y registra la compra, todo en una transaccion.
     */
    public function purchase(Request $request, StoreItem $item): JsonResponse
    {
        $buyer = $request->user();

        if ($item->status !== StoreItem::STATUS_ACTIVE) {
            return response()->json(['message' => 'Este item ya no esta disponible.'], 422);
        }

        if ($item->seller_id === $buyer->id) {
            return response()->json(['message' => 'No puedes comprar tu propio item.'], 422);
        }

        if ($buyer->coins < $item->price) {
            return response()->json([
                'message' => 'No tienes suficientes coins.',
                'your_coins' => $buyer->coins,
                'price' => $item->price,
            ], 402);
        }

        $purchase = DB::transaction(function () use ($buyer, $item): StorePurchase {
            // Recarga con bloqueo para evitar carreras de saldo.
            $buyer = \App\Models\User::whereKey($buyer->id)->lockForUpdate()->firstOrFail();
            $seller = \App\Models\User::whereKey($item->seller_id)->lockForUpdate()->firstOrFail();

            $buyer->decrement('coins', $item->price);
            $seller->increment('coins', $item->price);

            return StorePurchase::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'item_id' => $item->id,
                'price_paid' => $item->price,
            ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Compra realizada con exito.',
            'data' => [
                'purchase_id' => $purchase->id,
                'item_id' => $item->id,
                'price_paid' => $purchase->price_paid,
                'coins_remaining' => $buyer->fresh()->coins,
            ],
        ], 201);
    }

    /**
     * Cosmeticos que yo he comprado (para elegir cual aplicar).
     */
    public function myCosmetics(Request $request): AnonymousResourceCollection
    {
        $items = StoreItem::query()
            ->whereNotNull('cosmetic_type')
            ->whereHas('purchases', fn ($q) => $q->where('buyer_id', $request->user()->id))
            ->get();

        return StoreItemResource::collection($items);
    }

    /**
     * Aplica un cosmético comprado al perfil del usuario (frame, tema de
     * chat o título). Devuelve el usuario actualizado.
     */
    public function apply(Request $request, StoreItem $item): JsonResponse
    {
        $user = $request->user();

        if ($item->cosmetic_type === null) {
            return response()->json(['message' => 'Este item no es un cosmético aplicable.'], 422);
        }

        $owns = StorePurchase::query()
            ->where('buyer_id', $user->id)
            ->where('item_id', $item->id)
            ->exists();

        if (! $owns) {
            return response()->json(['message' => 'Debes comprar este cosmético antes de aplicarlo.'], 403);
        }

        $updates = match ($item->cosmetic_type) {
            StoreItem::COSMETIC_FRAME => ['active_frame_id' => $item->id],
            StoreItem::COSMETIC_CHAT_THEME => ['active_chat_theme_id' => $item->id],
            StoreItem::COSMETIC_TITLE => [
                'custom_title' => $item->preview_data['text'] ?? $item->title,
            ],
            default => null,
        };

        if ($updates === null) {
            return response()->json(['message' => 'Tipo de cosmético no soportado.'], 422);
        }

        $user->forceFill($updates)->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Cosmético aplicado.',
            'data' => (new UserResource($user->fresh()))->toArray($request),
        ]);
    }
}
