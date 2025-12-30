<?php

namespace App\Filament\Widgets;

use App\Models\Book;
use Filament\Widgets\ChartWidget;

class BooksAvailabilityChart extends ChartWidget
{
    protected string $color = 'success';
    protected ?string $heading = 'Books availability';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $available = Book::query()->where('is_available', true)->count();
        $unavailable = Book::query()->where('is_available', false)->count();

        return [
            'labels' => ['Available', 'Unavailable'],
            'datasets' => [
                [
                    'data' => [$available, $unavailable],
                ],
            ],
        ];
    }
}
