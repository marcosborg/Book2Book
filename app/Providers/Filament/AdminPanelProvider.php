<?php

namespace App\Providers\Filament;

use App\Filament\Resources\BookResource;
use App\Filament\Resources\ReportResource;
use App\Filament\Resources\TradeRequestResource;
use App\Filament\Resources\UserResource;
use App\Filament\Widgets\BooksAvailabilityChart;
use App\Filament\Widgets\MvpStatsWidget;
use App\Filament\Widgets\TradesStatusChart;
use App\Filament\Widgets\UsersGrowthChart;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                BookResource::class,
                ReportResource::class,
                TradeRequestResource::class,
                UserResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                MvpStatsWidget::class,
                UsersGrowthChart::class,
                BooksAvailabilityChart::class,
                TradesStatusChart::class,
            ])
            ->userMenuItems([
                Action::make('landing')
                    ->label('Landing page')
                    ->icon(Heroicon::OutlinedHome)
                    ->url(url('/'))
                    ->sort(50),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
