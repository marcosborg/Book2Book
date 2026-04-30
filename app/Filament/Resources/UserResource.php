<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('phone')
                    ->maxLength(30),
                TextInput::make('city')
                    ->maxLength(120)
                    ->live(onBlur: true),
                TextInput::make('postal_code')
                    ->label('Postal code')
                    ->maxLength(20)
                    ->live(onBlur: true)
                    ->suffixAction(
                        Action::make('geocodeAddress')
                            ->label('Find coordinates')
                            ->icon('heroicon-m-map-pin')
                            ->action(function (Get $get, Set $set): void {
                                $coordinates = self::geocodeAddress($get('city'), $get('postal_code'));

                                if (! $coordinates) {
                                    Notification::make()
                                        ->title('Coordinates not found')
                                        ->body('Check the city and postal code, then try again.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $set('lat', $coordinates['lat']);
                                $set('lng', $coordinates['lng']);

                                Notification::make()
                                    ->title('Coordinates filled')
                                    ->body("Latitude {$coordinates['lat']}, longitude {$coordinates['lng']}.")
                                    ->success()
                                    ->send();
                            })
                    ),
                TextInput::make('lat')
                    ->label('Latitude')
                    ->numeric()
                    ->helperText('Filled automatically from city/postal code, but can be adjusted.'),
                TextInput::make('lng')
                    ->label('Longitude')
                    ->numeric()
                    ->helperText('Filled automatically from city/postal code, but can be adjusted.'),
                TextInput::make('password')
                    ->password()
                    ->required(fn (string $context) => $context === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                Toggle::make('is_admin')
                    ->label('Backoffice access'),
                Toggle::make('is_blocked'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('city')->toggleable(),
                TextColumn::make('postal_code')->toggleable(),
                ToggleColumn::make('is_admin')
                    ->label('Admin'),
                ToggleColumn::make('is_blocked'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    /**
     * @return array{lat: string, lng: string}|null
     */
    private static function geocodeAddress(?string $city, ?string $postalCode): ?array
    {
        $query = collect([$postalCode, $city, 'Portugal'])
            ->filter(fn (?string $part): bool => filled($part))
            ->implode(', ');

        if (blank($query)) {
            return null;
        }

        try {
            $result = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => 'Book2Book/1.0',
                ])
                ->timeout(8)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 1,
                    'countrycodes' => 'pt',
                ])
                ->json('0');
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
            return null;
        }

        return [
            'lat' => number_format((float) $result['lat'], 7, '.', ''),
            'lng' => number_format((float) $result['lon'], 7, '.', ''),
        ];
    }
}
