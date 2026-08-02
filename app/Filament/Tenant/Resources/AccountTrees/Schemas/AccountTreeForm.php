<?php

namespace App\Filament\Tenant\Resources\AccountTrees\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AccountTreeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->columns(2)
                    ->schema([
                    TextInput::make('account_name')->label(__('dashboard.resources.account_tree.account_name'))->required(),
                    TextInput::make('account_code')->label(__('dashboard.resources.account_tree.account_code'))->required(),
                    Select::make('account_type')->label(__('dashboard.resources.account_tree.account_type'))
                        ->options([
                            'debit' => "debit",
                            'credit' => "credit",
                        ])
                        ->required(),


                   Select::make('branch_id')->label(__('dashboard.resources.account_tree.branch'))
                       ->relationship('branch', 'name') ,

                   Select::make('parent_id')->label(__('dashboard.resources.account_tree.parent_account'))

                       ->relationship('parent', 'account_name')  ->searchable(),



                    TextInput::make('level')->label(__('dashboard.resources.account_tree.level'))->integer()->required(),


                        Select::make('income_general_statement')
                            ->label(__('dashboard.resources.account_tree.statement'))
                            ->options([
                                'income' => __('dashboard.resources.account_tree.statement_income'),
                                'general' => __('dashboard.resources.account_tree.statement_general'),
                                'none' => __('dashboard.resources.account_tree.statement_none'),
                            ])
                            ->required(),



                    TextInput::make('order')->label(__('dashboard.resources.account_tree.order'))->required(),


                    Select::make('main_acc_status')->label(__('dashboard.resources.account_tree.main_sub'))
                        ->options([
                            'main' => __('dashboard.resources.account_tree.main'),
                            'sub' => __('dashboard.resources.account_tree.sub'),
                        ])
                        ->required(),



                ])

            ]);
    }
}
