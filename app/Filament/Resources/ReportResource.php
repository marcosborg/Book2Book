<?php

namespace App\Filament\Resources;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Filament\Resources\ReportResource\Pages;
use App\Models\Report;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-flag';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('reporter_id')
                    ->relationship('reporter', 'name')
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->options(array_combine(
                        array_column(ReportType::cases(), 'value'),
                        array_column(ReportType::cases(), 'value')
                    ))
                    ->required(),
                TextInput::make('entity_id')
                    ->numeric()
                    ->required(),
                Textarea::make('reason')
                    ->rows(4)
                    ->columnSpanFull()
                    ->required(),
                Select::make('status')
                    ->options(array_combine(
                        array_column(ReportStatus::cases(), 'value'),
                        array_column(ReportStatus::cases(), 'value')
                    ))
                    ->required(),
                DateTimePicker::make('resolved_at'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('type')->sortable(),
                TextColumn::make('entity_id')->label('Entity'),
                TextColumn::make('reporter.name')->label('Reporter')->searchable(),
                TextColumn::make('status')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
