<?php

namespace App\Filament\Pages;

use App\Services\DatabaseCloneService;
use App\Services\DatabaseEnvironmentService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;

class DatabaseEnvironment extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = Heroicon::OutlinedCircleStack;
    protected static string | \UnitEnum | null $navigationGroup = 'System';
    protected static ?int $navigationSort = 30;
    protected static ?string $title = 'Database Environment';

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Current database')
                ->schema([
                    Text::make(fn () => $this->modeLabel())->badge(),
                    Text::make(fn () => 'Host: '.$this->currentHost()),
                    Text::make(fn () => 'Database: '.$this->currentDatabase()),
                ])
                ->columns(1),
            Section::make('Switch environment')
                ->schema([
                    Actions::make([
                        $this->switchToSandboxAction(),
                        $this->switchToProductionAction(),
                    ]),
                ]),
            Section::make('Clone data')
                ->schema([
                    Text::make('This overwrites the target database. Use with care.')->color('warning'),
                    Actions::make([
                        $this->cloneProductionToSandboxAction(),
                        $this->cloneSandboxToProductionAction(),
                    ]),
                ]),
            Section::make('Storage')
                ->schema([
                    Text::make(fn () => $this->storageLinkStatus())->badge(),
                    Text::make('Creates or refreshes the public/storage link used by uploaded book covers.'),
                    Actions::make([
                        $this->storageLinkAction(),
                    ]),
                ]),
        ]);
    }

    private function modeLabel(): string
    {
        $mode = app(DatabaseEnvironmentService::class)->getMode();

        return $mode === 'production' ? 'Mode: production' : 'Mode: sandbox';
    }

    private function currentHost(): string
    {
        return (string) config('database.connections.'.$this->currentConnection().'.host', 'n/a');
    }

    private function currentDatabase(): string
    {
        return (string) config('database.connections.'.$this->currentConnection().'.database', 'n/a');
    }

    private function currentConnection(): string
    {
        return app(DatabaseEnvironmentService::class)->getConnectionName();
    }

    private function storageLinkStatus(): string
    {
        return file_exists(public_path('storage'))
            ? 'Storage link: available'
            : 'Storage link: missing';
    }

    private function switchToSandboxAction(): Action
    {
        return Action::make('switchToSandbox')
            ->label('Use sandbox')
            ->icon(Heroicon::OutlinedBeaker)
            ->visible(fn () => $this->currentConnection() !== 'sandbox')
            ->requiresConfirmation()
            ->action(function (): void {
                $service = app(DatabaseEnvironmentService::class);
                $service->setMode('sandbox');
                $service->apply();
                Notification::make()->title('Switched to sandbox')->success()->send();
            });
    }

    private function switchToProductionAction(): Action
    {
        return Action::make('switchToProduction')
            ->label('Use production')
            ->color('danger')
            ->icon(Heroicon::OutlinedServerStack)
            ->visible(fn () => $this->currentConnection() !== 'production')
            ->requiresConfirmation()
            ->action(function (): void {
                $service = app(DatabaseEnvironmentService::class);
                $service->setMode('production');
                $service->apply();
                Notification::make()->title('Switched to production')->warning()->send();
            });
    }

    private function cloneProductionToSandboxAction(): Action
    {
        return Action::make('cloneProductionToSandbox')
            ->label('Clone production -> sandbox')
            ->color('warning')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->requiresConfirmation()
            ->action(function (): void {
                $result = app(DatabaseCloneService::class)->clone('production', 'sandbox');
                $created = count($result['created_tables']);
                Notification::make()
                    ->title('Production cloned to sandbox')
                    ->body("Tables copied: {$result['tables']} | Tables created: {$created}")
                    ->success()
                    ->send();
                if (! empty($result['missing_in_target'])) {
                    Notification::make()
                        ->title('Some tables are missing in sandbox')
                        ->body(implode(', ', $result['missing_in_target']))
                        ->warning()
                        ->send();
                }
            });
    }

    private function cloneSandboxToProductionAction(): Action
    {
        return Action::make('cloneSandboxToProduction')
            ->label('Clone sandbox -> production')
            ->color('danger')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->requiresConfirmation()
            ->action(function (): void {
                $result = app(DatabaseCloneService::class)->clone('sandbox', 'production');
                $created = count($result['created_tables']);
                Notification::make()
                    ->title('Sandbox cloned to production')
                    ->body("Tables copied: {$result['tables']} | Tables created: {$created}")
                    ->warning()
                    ->send();
                if (! empty($result['missing_in_target'])) {
                    Notification::make()
                        ->title('Some tables are missing in production')
                        ->body(implode(', ', $result['missing_in_target']))
                        ->danger()
                        ->send();
                }
            });
    }

    private function storageLinkAction(): Action
    {
        return Action::make('storageLink')
            ->label('Generate storage link')
            ->icon(Heroicon::OutlinedLink)
            ->requiresConfirmation()
            ->modalHeading('Generate public storage link?')
            ->modalDescription('This runs php artisan storage:link --force so uploaded files can be served from public/storage.')
            ->action(function (): void {
                $exitCode = Artisan::call('storage:link', [
                    '--force' => true,
                ]);

                $output = trim(Artisan::output());

                if ($exitCode !== 0) {
                    Notification::make()
                        ->title('Storage link failed')
                        ->body($output ?: 'The storage link command returned an error.')
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('Storage link ready')
                    ->body($output ?: 'public/storage now points to storage/app/public.')
                    ->success()
                    ->send();
            });
    }
}
