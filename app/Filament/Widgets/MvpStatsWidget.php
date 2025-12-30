<?php

namespace App\Filament\Widgets;

use App\Enums\TradeStatus;
use App\Models\Book;
use App\Models\TradeRequest;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MvpStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Users', User::count()),
            Stat::make('Books Available', Book::where('is_available', true)->count()),
            Stat::make('Trades Pending', TradeRequest::where('status', TradeStatus::Pending)->count()),
        ];
    }
}
