<?php

namespace App\Filament\Resources;

use App\Enums\PsbStatus;
use App\Filament\Resources\PsbOrderResource\Pages;
use App\Models\PsbOrder;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ViewAction;

class PsbOrderResource extends Resource
{
    protected static ?string $model = PsbOrder::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'PSB Orders';
    protected static ?string $navigationGroup = 'PSB';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Customer')->schema([
                Forms\Components\TextInput::make('customer_name')->required(),
                Forms\Components\TextInput::make('customer_phone')->required(),
                Forms\Components\TextInput::make('customer_nik'),
                Forms\Components\Textarea::make('customer_address'),
            ])->columns(2),
            Forms\Components\Section::make('Network')->schema([
                Forms\Components\TextInput::make('router_name'),
                Forms\Components\TextInput::make('pppoe_user')->disabled(),
                Forms\Components\TextInput::make('pppoe_password')->disabled(),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('Order #')->sortable(),
                TextColumn::make('customer_name')->searchable()->sortable(),
                TextColumn::make('village'),
                TextColumn::make('package')->badge(),
                BadgeColumn::make('status')
                    ->colors([
                        'gray'    => 'draft',
                        'blue'    => 'submitted',
                        'cyan'    => 'coverage_ok',
                        'warning' => 'assigned',
                        'orange'  => 'provisioning',
                        'purple'  => 'photos',
                        'success' => 'done',
                        'danger'  => 'rejected',
                    ]),
                TextColumn::make('olt_type')->badge(),
                TextColumn::make('teknisi.name')
                    ->label('Teknisi')
                    ->formatStateUsing(fn ($record) => $record->teknisi->pluck('name')->join(', ')),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PsbStatus::cases())->pluck('value', 'value')),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('revert')
                    ->visible(fn ($record) => $record->status === PsbStatus::Rejected)
                    ->action(fn ($record) => app(\App\Services\PsbStateMachine::class)
                        ->revertFromRejected($record, auth()->user(), 'Manual revert by admin'))
                    ->color('warning')
                    ->icon('heroicon-o-arrow-uturn-left'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPsbOrders::route('/'),
            'view'  => Pages\ViewPsbOrder::route('/{record}'),
        ];
    }
}
