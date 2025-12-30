<?php

namespace App\Filament\Resources;

use App\Enums\BookCondition;
use App\Filament\Resources\BookResource\Pages;
use App\Models\Book;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-book-open';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('user_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                TextInput::make('author')
                    ->required()
                    ->maxLength(255),
                TextInput::make('isbn')
                    ->maxLength(32),
                TextInput::make('genre')
                    ->maxLength(120),
                TextInput::make('language')
                    ->maxLength(60),
                Select::make('condition')
                    ->options(array_combine(
                        array_column(BookCondition::cases(), 'value'),
                        array_column(BookCondition::cases(), 'value')
                    ))
                    ->required(),
                Textarea::make('description')
                    ->rows(4)
                    ->columnSpanFull(),
                FileUpload::make('cover_image_path')
                    ->disk('public')
                    ->directory('covers')
                    ->image()
                    ->imagePreviewHeight(120),
                Toggle::make('is_available'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image_path')->disk('public')->label('Cover'),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('author')->searchable(),
                TextColumn::make('owner.name')->label('Owner')->sortable(),
                TextColumn::make('condition'),
                ToggleColumn::make('is_available'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit' => Pages\EditBook::route('/{record}/edit'),
        ];
    }
}
