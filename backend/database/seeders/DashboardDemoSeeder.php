<?php

namespace Database\Seeders;

use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Table\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DashboardDemoSeeder extends Seeder
{
    private const DEMO_SEED = 20260423;

    public function run(): void
    {
        mt_srand(self::DEMO_SEED);

        $restaurant = EloquentRestaurant::query()->first();

        if (! $restaurant) {
            return;
        }

        $users = EloquentUser::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereNull('deleted_at')
            ->get();

        $tables = EloquentTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereNull('deleted_at')
            ->get();

        $products = EloquentProduct::query()
            ->with('tax')
            ->where('restaurant_id', $restaurant->id)
            ->whereNull('deleted_at')
            ->get();

        if ($users->isEmpty() || $tables->isEmpty() || $products->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($restaurant, $users, $tables, $products): void {
            $this->resetTransactions($restaurant->id);
            $this->seedHistoricalSales($restaurant, $users, $tables, $products);
        });
    }

    private function resetTransactions(int $restaurantId): void
    {
        $orderIds = DB::table('orders')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id');

        $saleIds = DB::table('sales')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id');

        if ($saleIds->isNotEmpty()) {
            $saleLineIds = DB::table('sales_lines')
                ->whereIn('sale_id', $saleIds)
                ->pluck('id');

            if (Schema::hasTable('refund_lines') && $saleLineIds->isNotEmpty()) {
                DB::table('refund_lines')->whereIn('sale_line_id', $saleLineIds)->delete();
            }

            if (Schema::hasTable('refunds')) {
                DB::table('refunds')->whereIn('sale_id', $saleIds)->delete();
            }

            DB::table('sales_lines')->whereIn('sale_id', $saleIds)->delete();
            DB::table('sales')->whereIn('id', $saleIds)->delete();
        }

        if ($orderIds->isNotEmpty()) {
            DB::table('payments')->whereIn('order_id', $orderIds)->delete();
            DB::table('order_lines')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->whereIn('id', $orderIds)->delete();
        }

        if (Schema::hasTable('cash_shifts')) {
            DB::table('cash_shifts')->where('restaurant_id', $restaurantId)->delete();
        }

        DB::table('restaurant_ticket_counters')->updateOrInsert(
            ['restaurant_id' => $restaurantId],
            ['last_ticket_number' => 0, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    private function seedHistoricalSales(
        EloquentRestaurant $restaurant,
        Collection $users,
        Collection $tables,
        Collection $products,
    ): void {
        $serviceUsers = $users->filter(fn (EloquentUser $user) => in_array($user->role, ['waiter', 'supervisor', 'admin'], true))->values();
        $productsByName = $products->keyBy('name');
        $ticketNumber = 1;

        foreach (range(44, 0) as $daysAgo) {
            $day = CarbonImmutable::now()->subDays($daysAgo);

            foreach (range(1, $this->ordersForDay($day)) as $serviceIndex) {
                $table = $tables->get(mt_rand(0, $tables->count() - 1));
                $user = $serviceUsers->get(mt_rand(0, $serviceUsers->count() - 1));
                $diners = $this->dinersForService($day, $serviceIndex);
                $closedAt = $this->serviceTime($day, $serviceIndex);
                $openedAt = $closedAt->subMinutes(mt_rand(28, 95));

                $lineDrafts = $this->buildLineDrafts($productsByName, $products, $diners);
                $totals = $this->calculateTotals($lineDrafts);

                $orderId = DB::table('orders')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'restaurant_id' => $restaurant->id,
                    'status' => 'invoiced',
                    'table_id' => $table->id,
                    'opened_by_user_id' => $user->id,
                    'closed_by_user_id' => $user->id,
                    'diners' => $diners,
                    'discount_type' => $totals['order_discount_type'],
                    'discount_value' => $totals['order_discount_value'],
                    'discount_amount' => $totals['order_discount_total'],
                    'opened_at' => $openedAt,
                    'closed_at' => $closedAt,
                    'created_at' => $openedAt,
                    'updated_at' => $closedAt,
                ]);

                $orderLineIds = [];

                foreach ($lineDrafts as $index => $lineDraft) {
                    $lineTimestamp = $openedAt->addMinutes(6 + ($index * 4));

                    $orderLineIds[] = DB::table('order_lines')->insertGetId([
                        'uuid' => (string) Str::uuid(),
                        'restaurant_id' => $restaurant->id,
                        'order_id' => $orderId,
                        'product_id' => $lineDraft['product']->id,
                        'user_id' => $user->id,
                        'quantity' => $lineDraft['quantity'],
                        'price' => $lineDraft['price'],
                        'tax_percentage' => $lineDraft['tax_percentage'],
                        'discount_type' => $lineDraft['discount_type'],
                        'discount_value' => $lineDraft['discount_value'],
                        'discount_amount' => $lineDraft['discount_amount'],
                        'sent_to_kitchen_at' => $lineTimestamp,
                        'created_at' => $lineTimestamp,
                        'updated_at' => $lineTimestamp,
                    ]);
                }

                $saleId = DB::table('sales')->insertGetId([
                    'uuid' => (string) Str::uuid(),
                    'restaurant_id' => $restaurant->id,
                    'order_id' => $orderId,
                    'user_id' => $user->id,
                    'ticket_number' => $ticketNumber,
                    'value_date' => $closedAt,
                    'subtotal' => $totals['subtotal'],
                    'tax_amount' => $totals['tax_amount'],
                    'line_discount_total' => $totals['line_discount_total'],
                    'order_discount_total' => $totals['order_discount_total'],
                    'total' => $totals['total'],
                    'refunded_total' => 0,
                    'created_at' => $closedAt,
                    'updated_at' => $closedAt,
                ]);

                foreach ($lineDrafts as $index => $lineDraft) {
                    $lineTimestamp = $closedAt->subMinutes(max(1, count($lineDrafts) - $index));

                    DB::table('sales_lines')->insert([
                        'uuid' => (string) Str::uuid(),
                        'restaurant_id' => $restaurant->id,
                        'sale_id' => $saleId,
                        'order_line_id' => $orderLineIds[$index],
                        'user_id' => $user->id,
                        'quantity' => $lineDraft['quantity'],
                        'price' => $lineDraft['price'],
                        'tax_percentage' => $lineDraft['tax_percentage'],
                        'line_subtotal' => $lineDraft['line_subtotal'],
                        'tax_amount' => $lineDraft['tax_amount'],
                        'discount_type' => $lineDraft['discount_type'],
                        'discount_value' => $lineDraft['discount_value'],
                        'discount_amount' => $lineDraft['discount_amount'],
                        'line_total' => $lineDraft['line_total'],
                        'refunded_quantity' => 0,
                        'created_at' => $lineTimestamp,
                        'updated_at' => $lineTimestamp,
                    ]);
                }

                foreach ($this->buildPayments($totals['total']) as $payment) {
                    DB::table('payments')->insert([
                        'uuid' => (string) Str::uuid(),
                        'order_id' => $orderId,
                        'user_id' => $user->id,
                        'amount' => $payment['amount'],
                        'method' => $payment['method'],
                        'description' => $payment['description'],
                        'created_at' => $closedAt,
                        'updated_at' => $closedAt,
                    ]);
                }

                $ticketNumber++;
            }
        }

        DB::table('restaurant_ticket_counters')->updateOrInsert(
            ['restaurant_id' => $restaurant->id],
            ['last_ticket_number' => $ticketNumber - 1, 'created_at' => now(), 'updated_at' => now()],
        );
    }

    private function ordersForDay(CarbonImmutable $day): int
    {
        if ($day->isFriday()) {
            return mt_rand(5, 7);
        }

        if ($day->isWeekend()) {
            return mt_rand(6, 9);
        }

        return mt_rand(3, 5);
    }

    private function dinersForService(CarbonImmutable $day, int $serviceIndex): int
    {
        $base = $day->isWeekend() ? 3 : 2;
        $boost = $serviceIndex % 3 === 0 ? 1 : 0;

        return max(2, min(6, $base + $boost + mt_rand(0, 2)));
    }

    private function serviceTime(CarbonImmutable $day, int $serviceIndex): CarbonImmutable
    {
        $isDinner = $serviceIndex % 2 === 0;

        if ($isDinner) {
            return $day->setTime(mt_rand(20, 22), mt_rand(0, 59), 0);
        }

        return $day->setTime(mt_rand(13, 15), mt_rand(0, 59), 0);
    }

    private function buildLineDrafts(Collection $productsByName, Collection $products, int $diners): array
    {
        $usedProducts = [];
        $lines = [];

        $lines[] = $this->makeLineDraft(
            $this->pickProduct($productsByName, $products, ['Coca-Cola', 'Agua Mineral', 'Fanta Naranja'], $usedProducts),
            max(1, $diners + mt_rand(-1, 1)),
        );

        if (mt_rand(1, 100) <= 78) {
            $lines[] = $this->makeLineDraft(
                $this->pickProduct($productsByName, $products, ['Croquetas de Jamón', 'Patatas Bravas', 'Ensalada Valenciana', 'Ensalada Griega'], $usedProducts),
                max(1, (int) ceil($diners / 2)),
            );
        }

        $mainLines = $diners >= 4 ? 2 : 1;

        for ($index = 0; $index < $mainLines; $index++) {
            $lines[] = $this->makeLineDraft(
                $this->pickProduct($productsByName, $products, ['Pizza Margarita', 'Pizza Pepperoni', 'Lasaña de Carne', 'Lasaña Vegetariana', 'Pollo Empanado'], $usedProducts),
                max(1, (int) ceil($diners / $mainLines)),
            );
        }

        if (mt_rand(1, 100) <= 42) {
            $lines[] = $this->makeLineDraft(
                $this->pickProduct($productsByName, $products, ['Patatas Fritas', 'Arroz Blanco', 'Ensalada Mixta'], $usedProducts),
                mt_rand(1, 2),
            );
        }

        if (mt_rand(1, 100) <= 36) {
            $lines[] = $this->makeLineDraft(
                $this->pickProduct($productsByName, $products, ['Tarta de Queso', 'Tarta de Chocolate', 'Helado de Vainilla', 'Helado de Chocolate'], $usedProducts),
                mt_rand(1, min(3, $diners)),
            );
        }

        return array_values(array_filter($lines));
    }

    private function makeLineDraft(?EloquentProduct $product, int $quantity): ?array
    {
        if (! $product || ! $product->tax) {
            return null;
        }

        $lineSubtotal = $quantity * (int) $product->price;
        $applyDiscount = $lineSubtotal >= 900 && mt_rand(1, 100) <= 12;
        $discountType = $applyDiscount ? 'percentage' : null;
        $discountValue = $applyDiscount ? 10 : 0;
        $discountAmount = $applyDiscount ? (int) round($lineSubtotal * ($discountValue / 100)) : 0;
        $taxableBase = max(0, $lineSubtotal - $discountAmount);
        $taxAmount = (int) round($taxableBase * (((int) $product->tax->percentage) / 100));

        return [
            'product' => $product,
            'quantity' => $quantity,
            'price' => (int) $product->price,
            'tax_percentage' => (int) $product->tax->percentage,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'line_subtotal' => $lineSubtotal,
            'tax_amount' => $taxAmount,
            'line_total' => $taxableBase + $taxAmount,
        ];
    }

    private function calculateTotals(array $lineDrafts): array
    {
        $subtotal = array_sum(array_column($lineDrafts, 'line_subtotal'));
        $taxAmount = array_sum(array_column($lineDrafts, 'tax_amount'));
        $lineDiscountTotal = array_sum(array_column($lineDrafts, 'discount_amount'));
        $preDiscountTotal = $subtotal - $lineDiscountTotal;

        $orderDiscountType = null;
        $orderDiscountValue = 0;
        $orderDiscountTotal = 0;

        if ($preDiscountTotal >= 3200 && mt_rand(1, 100) <= 18) {
            if (mt_rand(0, 1) === 1) {
                $orderDiscountType = 'percentage';
                $orderDiscountValue = 10;
                $orderDiscountTotal = (int) round($preDiscountTotal * 0.10);
            } else {
                $orderDiscountType = 'amount';
                $orderDiscountValue = mt_rand(250, 500);
                $orderDiscountTotal = min($preDiscountTotal, $orderDiscountValue);
            }
        }

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'line_discount_total' => $lineDiscountTotal,
            'order_discount_type' => $orderDiscountType,
            'order_discount_value' => $orderDiscountValue,
            'order_discount_total' => $orderDiscountTotal,
            'total' => max(0, $preDiscountTotal - $orderDiscountTotal + $taxAmount),
        ];
    }

    private function buildPayments(int $total): array
    {
        if ($total <= 0) {
            return [[
                'amount' => 0,
                'method' => 'cash',
                'description' => 'Pago demo',
            ]];
        }

        if ($total >= 4000 && mt_rand(1, 100) <= 22) {
            $firstAmount = (int) round($total * 0.6);
            $firstMethod = $this->pickPaymentMethod();
            $secondMethod = $firstMethod === 'cash' ? 'card' : 'cash';

            return [
                [
                    'amount' => $firstAmount,
                    'method' => $firstMethod,
                    'description' => 'Pago principal demo',
                ],
                [
                    'amount' => $total - $firstAmount,
                    'method' => $secondMethod,
                    'description' => 'Pago combinado demo',
                ],
            ];
        }

        return [[
            'amount' => $total,
            'method' => $this->pickPaymentMethod(),
            'description' => 'Pago demo',
        ]];
    }

    private function pickPaymentMethod(): string
    {
        $roll = mt_rand(1, 100);

        if ($roll <= 42) {
            return 'cash';
        }

        if ($roll <= 85) {
            return 'card';
        }

        return 'bizum';
    }

    private function pickProduct(Collection $productsByName, Collection $products, array $preferredNames, array &$usedProducts): ?EloquentProduct
    {
        $availablePreferred = array_values(array_filter(
            $preferredNames,
            fn (string $name) => $productsByName->has($name) && ! in_array($productsByName->get($name)->id, $usedProducts, true),
        ));

        if ($availablePreferred !== []) {
            $selectedName = $availablePreferred[mt_rand(0, count($availablePreferred) - 1)];
            $selectedProduct = $productsByName->get($selectedName);
            $usedProducts[] = $selectedProduct->id;

            return $selectedProduct;
        }

        $fallbackProducts = $products
            ->filter(fn (EloquentProduct $product) => ! in_array($product->id, $usedProducts, true))
            ->values();

        if ($fallbackProducts->isEmpty()) {
            $fallbackProducts = $products->values();
        }

        if ($fallbackProducts->isEmpty()) {
            return null;
        }

        $selectedProduct = $fallbackProducts->get(mt_rand(0, $fallbackProducts->count() - 1));
        $usedProducts[] = $selectedProduct->id;

        return $selectedProduct;
    }
}
