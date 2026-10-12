<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreItemRequest;
use App\Http\Resources\StoreItemResource;
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
}
