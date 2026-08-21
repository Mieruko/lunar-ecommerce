<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function adjust(Inventory $inventory, int $quantityDelta, string $transactionType, ?User $actor = null, ?string $notes = null): Inventory
    {
        if ($quantityDelta === 0) {
            throw ValidationException::withMessages(['quantity_delta' => 'Số lượng điều chỉnh phải khác 0.']);
        }

        return DB::transaction(function () use ($inventory, $quantityDelta, $transactionType, $actor, $notes) {
            $locked = Inventory::query()->lockForUpdate()->findOrFail($inventory->id);
            $newOnHand = $locked->quantity_on_hand + $quantityDelta;

            if ($newOnHand < $locked->quantity_reserved) {
                throw ValidationException::withMessages(['quantity_delta' => 'Không thể giảm tồn kho thấp hơn số lượng đang giữ chỗ.']);
            }

            $locked->update(['quantity_on_hand' => $newOnHand]);
            InventoryTransaction::create([
                'warehouse_id' => $locked->warehouse_id,
                'product_variant_id' => $locked->product_variant_id,
                'transaction_type' => $transactionType,
                'quantity_delta' => $quantityDelta,
                'reference_type' => $actor ? User::class : null,
                'reference_id' => $actor?->id,
                'notes' => $notes,
            ]);

            return $locked->refresh();
        });
    }
}
