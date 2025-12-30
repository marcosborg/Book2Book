<?php

namespace App\Filament\Resources;

use App\Enums\TradeStatus;
use App\Filament\Resources\TradeRequestResource\Pages;
use App\Models\TradeRequest;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TradeRequestResource extends Resource
{
    protected static ?string $model = TradeRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('book_id')
                    ->relationship('book', 'title')
                    ->searchable()
                    ->required(),
                Select::make('requester_id')
                    ->relationship('requester', 'name')
                    ->searchable()
                    ->required(),
                Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->required(),
                Select::make('status')
                    ->options(array_combine(
                        array_column(TradeStatus::cases(), 'value'),
                        array_column(TradeStatus::cases(), 'value')
                    ))
                    ->required(),
                Textarea::make('message')
                    ->rows(4)
                    ->columnSpanFull(),
                DateTimePicker::make('accepted_at'),
                DateTimePicker::make('declined_at'),
                DateTimePicker::make('cancelled_at'),
                DateTimePicker::make('completed_at'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('book.title')->label('Book')->searchable(),
                TextColumn::make('requester.name')->label('Requester')->searchable(),
                TextColumn::make('owner.name')->label('Owner')->searchable(),
                TextColumn::make('status')->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTradeRequests::route('/'),
            'edit' => Pages\EditTradeRequest::route('/{record}/edit'),
        ];
    }
}
