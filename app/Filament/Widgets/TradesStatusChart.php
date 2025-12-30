<?php

namespace App\Filament\Widgets;

use App\Enums\TradeStatus;
use App\Models\TradeRequest;
use Filament\Widgets\ChartWidget;

class TradesStatusChart extends ChartWidget
{
    protected string $color = 'warning';
    protected ?string $heading = 'Trades by status';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $counts = TradeRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $labels = collect(TradeStatus::cases())->map(fn ($case) => $case->value)->all();
        $data = collect($labels)->map(fn ($label) => (int) ($counts[$label] ?? 0))->all();

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Trades',
                    'data' => $data,
                    'borderWidth' => 1,
                ],
            ],
        ];
    }
}
