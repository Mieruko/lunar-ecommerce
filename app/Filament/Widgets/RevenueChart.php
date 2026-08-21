<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Doanh thu 14 ngày gần nhất';

    protected function getData(): array
    {
        $payments = Payment::query()
            ->selectRaw('DATE(paid_at) as day, SUM(amount) as total')
            ->where('status', 'paid')
            ->whereBetween('paid_at', [now()->subDays(13)->startOfDay(), now()->endOfDay()])
            ->groupBy('day')
            ->pluck('total', 'day');

        $days = collect(range(13, 0))->map(fn (int $daysAgo) => now()->subDays($daysAgo)->toDateString());

        return [
            'datasets' => [[
                'label' => 'Doanh thu (VND)',
                'data' => $days->map(fn (string $day) => (int) ($payments[$day] ?? 0))->all(),
                'borderColor' => '#b7791f',
                'backgroundColor' => 'rgba(183, 121, 31, 0.12)',
                'fill' => true,
                'tension' => 0.35,
            ]],
            'labels' => $days->map(fn (string $day) => \Carbon\Carbon::parse($day)->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    public static function canView(): bool
    {
        return auth()->user()?->hasPermission('dashboard.view') ?? false;
    }
}
