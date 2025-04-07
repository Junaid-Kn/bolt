<?php

namespace LaraZeus\Bolt\Concerns;

use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use LaraZeus\Accordion\Forms\Accordion;
use LaraZeus\Bolt\BoltPlugin;
use LaraZeus\Bolt\Concerns\Options\DataSource;
use LaraZeus\Bolt\Concerns\Options\Visibility;
use LaraZeus\Bolt\Facades\Bolt;

trait HasOptions
{
    use DataSource;
    use Visibility;

    public static function required(): Grid
    {
        return Grid::make()
            ->schema([
                Toggle::make('options.is_required')->label(__('Is Required')),
            ])
            ->columns(1);
    }

    public static function hintOptions(): Accordion
    {
        return Accordion::make('hint-options')
            ->columns()
            ->label('Hint Options')
            ->icon('heroicon-o-light-bulb')
            ->schema([
                TextInput::make('options.hint.text')
                    ->label(__('Hint Text')),
                TextInput::make('options.hint.icon-tooltip')
                    ->label(__('Hint Icon tooltip')),
                ColorPicker::make('options.hint.color')->label(__('Hint Color')),
                // todo
                /*IconPicker::make('options.hint.icon')
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                        '2xl' => 5,
                    ])
                    ->label(__('Hint Icon')),*/
            ]);
    }

    public static function columnSpanFull(): Grid
    {
        return Grid::make()
            ->schema([
                Toggle::make('options.column_span_full')
                    ->helperText(__('show this field in full width row'))
                    ->label(__('Full Width')),
            ])
            ->columns(1);
    }

    public static function hiddenLabel(): Grid
    {
        return Grid::make()
            ->schema([
                Toggle::make('options.hidden_label')
                    ->label(__('Hidden Label')),
            ])
            ->columns(1);
    }

    public static function dataSource(): Grid
    {
        $dataSources = BoltPlugin::getModel('Collection')::get()
            ->mapWithKeys(function ($item, $key) {
                return [
                    $key => [
                        'title' => $item['name'],
                        'class' => $item['id'],
                    ],
                ];
            })
            ->toBase()
            ->merge(
                Bolt::availableDataSource()
                    ->mapWithKeys(function ($item, $key) {
                        return [
                            $key => [
                                'title' => $item['title'],
                                'class' => $item['class'],
                            ],
                        ];
                    })
            )
            ->pluck('title', 'class');

        return Grid::make()
            ->schema([
                Select::make('options.dataSource')
                    ->createOptionAction(fn (Action $action) => $action->hidden(auth()->user()->cannot('create', BoltPlugin::getModel('Collection'))))
                    ->required()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->live(onBlur: true)
                            ->label(__('Collections Name'))->required()->maxLength(255)->columnSpan(2),
                        Repeater::make('values')
                            ->grid([
                                'default' => 1,
                                'md' => 2,
                                'lg' => 3,
                            ])
                            ->label(__('Collections Values'))
                            ->columnSpan(2)
                            ->columns(1)
                            ->schema([
                                TextInput::make('itemValue')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Set $set, Get $get, string $operation) {
                                        $set('itemKey', $get('itemValue'));
                                    })
                                    ->required()->label(__('Value'))->hint(__('what the user will see')),
                                TextInput::make('itemKey')
                                    ->live(onBlur: true)
                                    ->required()->label(__('Key'))->hint(__('what store in the form')),
                                Toggle::make('itemIsDefault')->label(__('selected by default')),
                            ]),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $collectionModel = BoltPlugin::getModel('Collection');
                        $collection = new $collectionModel;
                        $collection->fill($data);
                        $collection->save();

                        return $collection->id;
                    })
                    ->options($dataSources)
                    ->label(__('Data Source')),
            ])
            ->columns(1);
    }

    public static function htmlID(): Grid
    {
        return Grid::make()
            ->schema([
                TextInput::make('options.htmlId')
                    ->required()
                    ->default(str()->random(6))
                    ->label(__('HTML ID')),
            ])
            ->columns(1);
    }

    public static function isActive(): Grid
    {
        return Grid::make()
            ->schema([
                Toggle::make('options.is_active')
                    ->default(1)
                    ->label(__('zeus-bolt::forms.options.is_active')),
            ])
            ->columns(1);
    }
}
