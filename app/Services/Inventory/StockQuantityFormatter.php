<?php

namespace App\Services\Inventory;

use App\Models\Product;

class StockQuantityFormatter
{
    public function format(?Product $product, int $quantity): string
    {
        if (! $product) {
            return number_format($quantity, 0, ',', '.');
        }

        if (! $product->relationLoaded('prices')) {
            $product->load('prices.unit');
        } else {
            $product->prices->loadMissing('unit');
        }

        return $this->formatUnits(
            $quantity,
            $product->prices->map(fn ($price) => [
                'conversion' => (int) $price->conversion,
                'code' => $price->unit?->code,
                'name' => $price->unit?->name,
            ])->all(),
        );
    }

    /**
     * @param  iterable<array{conversion: int, code?: string|null, name?: string|null}>  $units
     */
    public function formatUnits(int $quantity, iterable $units): string
    {
        $units = collect($units)
            ->filter(fn (array $unit) => ($unit['conversion'] ?? 0) > 0 && (($unit['code'] ?? null) || ($unit['name'] ?? null)))
            ->sortByDesc('conversion')
            ->unique('conversion')
            ->values();

        if ($units->isEmpty()) {
            return number_format($quantity, 0, ',', '.');
        }

        $smallestUnit = $units->sortBy('conversion')->first();
        $smallestUnitName = $smallestUnit['code'] ?: $smallestUnit['name'];

        if ($quantity === 0) {
            return '0 '.$smallestUnitName;
        }

        $isNegative = $quantity < 0;
        $remaining = abs($quantity);
        $result = [];

        foreach ($units as $unit) {
            $conversion = (int) $unit['conversion'];
            $unitQty = intdiv($remaining, $conversion);

            if ($unitQty <= 0) {
                continue;
            }

            $result[] = number_format($unitQty, 0, ',', '.').' '.($unit['code'] ?: $unit['name']);
            $remaining %= $conversion;
        }

        if ($remaining > 0) {
            $result[] = number_format($remaining, 0, ',', '.').' '.$smallestUnitName;
        }

        return ($isNegative ? '- ' : '').implode(', ', $result);
    }
}
