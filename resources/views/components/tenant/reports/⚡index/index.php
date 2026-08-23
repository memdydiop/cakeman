<?php

use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Reporting')] class extends Component
{
    public ?string $from = null;

    public ?string $to = null;

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->endOfMonth()->format('Y-m-d');
    }

    #[Computed]
    public function stats(): array
    {
        $from = $this->from ? Carbon::parse($this->from)->startOfDay() : now()->startOfMonth();
        $to = $this->to ? Carbon::parse($this->to)->endOfDay() : now()->endOfMonth();

        $orders = Order::whereBetween('created_at', [$from, $to])->where('status', '!=', 'cancelled')->with(['items.product', 'items.product.recipe.ingredients.ingredient'])->get();

        $ca = $orders->sum('total');
        $discount = $orders->sum('discount');
        $count = $orders->count();

        $cost = $orders->sum(function (Order $order): int {
            return $order->items->sum(function ($item): int {
                $recipe = $item->product->recipe ?? null;
                if (! $recipe) {
                    return 0;
                }
                $recipe->loadMissing('ingredients.ingredient');
                $unitCost = $recipe->cost();

                return $unitCost * $item->quantity;
            });
        });

        $margin = $ca - $cost;
        $marginPct = $ca > 0 ? round($margin / $ca * 100, 1) : 0;

        return compact('ca', 'cost', 'margin', 'marginPct', 'count', 'discount');
    }

    #[Computed]
    public function dailySeries(): array
    {
        $from = $this->from ? Carbon::parse($this->from) : now()->startOfMonth();
        $to = $this->to ? Carbon::parse($this->to) : now()->endOfMonth();

        $days = [];
        $period = CarbonPeriod::create($from, $to);

        foreach ($period as $day) {
            $days[$day->format('Y-m-d')] = 0;
        }

        Order::whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as d, SUM(total) as s')
            ->groupBy('d')
            ->pluck('s', 'd')
            ->each(function ($s, $d) use (&$days): void {
                if (array_key_exists($d, $days)) {
                    $days[$d] = (int) $s;
                }
            });

        return ['categories' => array_keys($days), 'data' => array_values($days)];
    }

    #[Computed]
    public function topProductsByMargin()
    {
        $from = $this->from ? Carbon::parse($this->from)->startOfDay() : now()->startOfMonth();
        $to = $this->to ? Carbon::parse($this->to)->endOfDay() : now()->endOfMonth();

        $items = OrderItem::whereHas('order', fn ($q) => $q->whereBetween('created_at', [$from, $to])->where('status', '!=', 'cancelled'))
            ->with(['product.recipe.ingredients.ingredient'])
            ->get()
            ->groupBy('product_id')
            ->map(function ($group) {
                $product = $group->first()->product;
                $revenue = $group->sum('line_total');
                $cost = $group->sum(function ($item): int {
                    $recipe = $item->product->recipe ?? null;
                    if (! $recipe) {
                        return 0;
                    }
                    $unitCost = $recipe->cost();

                    return $unitCost * $item->quantity;
                });

                return ['product' => $product, 'revenue' => $revenue, 'cost' => $cost, 'margin' => $revenue - $cost];
            })
            ->sortByDesc('margin')
            ->take(5)
            ->values();

        return $items;
    }
};
