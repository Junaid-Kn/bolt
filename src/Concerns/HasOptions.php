<?php

namespace LaraZeus\Bolt\Concerns;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Guava\FilamentIconPicker\Forms\IconPicker;
use LaraZeus\Accordion\Forms\Accordion;
use LaraZeus\Bolt\Concerns\Options\DataSource;
use LaraZeus\Bolt\Concerns\Options\Visibility;

trait HasOptions
{
    use DataSource;
    use Visibility;

    public static function required(): Grid
    {
        return Grid::make()
            ->schema([
                Toggle::make('options.is_required')
                    ->label(__('zeus-bolt::forms.options.is_required')),
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

    public static function hintOptions(): Accordion
    {
        return Accordion::make('hint-options')
            ->columns()
            ->label('Hint Options')
            ->icon('heroicon-o-light-bulb')
            ->schema([
                TextInput::make('options.hint.text')
                    ->label(__('zeus-bolt::forms.options.hint.text')),
                TextInput::make('options.hint.icon-tooltip')
                    ->label(__('zeus-bolt::forms.options.hint.icon_tooltip')),
                ColorPicker::make('options.hint.color')
                    ->label(__('zeus-bolt::forms.options.hint.color')),
                IconPicker::make('options.hint.icon')
                    ->columns([
                        'default' => 1,
                        'lg' => 3,
                        '2xl' => 5,
                    ])
                    ->label(__('zeus-bolt::forms.options.hint.label')),
            ]);
    }

    public static function columnSpanFull(): Grid
    {
        return Grid::make()
            ->schema([
                Toggle::make('options.column_span_full')
                    ->helperText(__('zeus-bolt::forms.options.column_span_full.help_text'))
                    ->label(__('zeus-bolt::forms.options.column_span_full.label')),
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
                    ->label(__('zeus-bolt::forms.options.html_id')),
            ])
            ->columns(1);
    }
}
