<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Mail\ContactClient;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Mail;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')->translateLabel()->required(),
                Forms\Components\TextInput::make('short')->translateLabel(),
                Forms\Components\ColorPicker::make('color')->translateLabel(),
                Forms\Components\Textarea::make('address')->translateLabel()->rows(4)->autosize()->required(),
                Forms\Components\TextInput::make('email')->translateLabel()->email(),
                Forms\Components\TextInput::make('phone')->translateLabel()->tel(),
                Forms\Components\Select::make('language')->translateLabel()->options([
                    'de' => 'DE',
                    'en' => 'EN',
                ])->native(false)->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColorColumn::make('color')->label(''),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()
                    ->description(fn (Client $record): string => $record->address)->wrap(),
                Tables\Columns\TextColumn::make('language')->translateLabel()->badge()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->translateLabel()->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('language')->translateLabel()->options([
                    'de' => 'DE',
                    'en' => 'EN',
                ])->native(false),
            ])
            ->actions(ActionGroup::make([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('kontaktieren')
                    ->icon('heroicon-o-envelope')
                    ->form(fn (Client $record) => [
                        TextInput::make('subject')->translateLabel()->required(),
                        RichEditor::make('content')->translateLabel()->required()
                            ->default(__("<p>Hi :Name,</p><p> </p><p>Best regards<br>Andreas Müller</p>", ['name' => $record->name])),
                    ])
                    ->action(function (Client $record, array $data) {
                        Mail::to($record->email)->send(
                            (new ContactClient(body: $data['content']))->subject($data['subject'])
                        );
                    })
            ]))
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('Clients');
    }

    public static function getModelLabel(): string
    {
        return __('Client');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Clients');
    }
}
