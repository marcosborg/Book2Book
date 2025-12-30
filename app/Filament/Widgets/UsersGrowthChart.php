<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsersGrowthChart extends ChartWidget
{
    protected string $color = 'info';
    protected ?string $heading = 'New users (last 14 days)';

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $dates = collect(range(13, 0))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('Y-m-d');
        });

        $counts = User::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', Carbon::today()->subDays(13)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        return [
            'labels' => $dates->map(fn ($date) => Carbon::parse($date)->format('d M'))->all(),
            'datasets' => [
                [
                    'label' => 'Users',
                    'data' => $dates->map(fn ($date) => (int) ($counts[$date] ?? 0))->all(),
                    'tension' => 0.35,
                    'fill' => true,
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
}
