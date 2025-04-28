<?php

namespace LaraZeus\Bolt\Concerns;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use LaraZeus\Accordion\Forms\Accordion;
use LaraZeus\Accordion\Forms\Accordions;
use LaraZeus\Bolt\BoltPlugin;
use LaraZeus\Bolt\Facades\Bolt;
use LaraZeus\Bolt\Models\Category;

trait Schemata
{
    protected static function getVisibleFields(array $sections, array $arguments): array
    {
        // @phpstan-ignore-next-line
        return collect($sections)
            ->map(function (array $sections) use ($arguments) {
                // @phpstan-ignore-next-line
                $sections['fields'] = collect($sections['fields'])
                    ->reject(function ($item, $key) use ($arguments) {
                        return $key === $arguments['item'] ||
                            ! (
                                isset($item['options']['dataSource']) ||
                                $item['type'] === '\LaraZeus\Bolt\Fields\Classes\Toggle'
                            );
                    })->all();

                return $sections;
            })->all();
    }

    protected static function sectionOptionsFormSchema(array $formOptions, array $allSections): array
    {
        return [
            TextInput::make('description')
                ->hidden(fn (Get $get) => $get('borderless') === true)
                ->nullable()
                ->live()
                ->visible($formOptions['show-as'] !== 'tabs')
                ->label(__('zeus-bolt::forms.section.description')),

            Accordions::make('section-options')
                ->accordions(fn () => array_filter([
                    Accordion::make('visual-options')
                        ->label(__('zeus-bolt::forms.section.options.visual_options'))
                        ->columns()
                        ->icon('tabler-list-details')
                        ->schema([
                            Select::make('columns')
                                ->options(fn (): array => array_combine(range(1, 12), range(1, 12)))
                                ->required()
                                ->default(1)
                                ->hint(__('zeus-bolt::forms.section.options.columns_hint'))
                                ->label(__('zeus-bolt::forms.section.options.columns_label')),
                            // todo
                            /*IconPicker::make('icon')
                                ->columns([
                                    'default' => 1,
                                    'lg' => 3,
                                    '2xl' => 5,
                                ])
                                ->visible(fn (Get $get) => $formOptions['show-as'] === 'page' && $get('borderless') === false)
                                ->label(__('zeus-bolt::forms.section.options.icon')),*/
                            Toggle::make('aside')
                                ->default(false)
                                ->visible(fn (Get $get) => $formOptions['show-as'] === 'page' && $get('borderless') === false)
                                ->label(__('zeus-bolt::forms.section.options.aside')),
                            Toggle::make('borderless')
                                ->live()
                                ->default(false)
                                ->visible($formOptions['show-as'] === 'page')
                                ->label(__('zeus-bolt::forms.section.options.borderless'))
                                ->helperText(__('zeus-bolt::forms.section.options.borderless_help')),
                            Toggle::make('compact')
                                ->default(false)
                                ->visible(fn (Get $get) => $formOptions['show-as'] === 'page' && $get('borderless') === false)
                                ->label(__('zeus-bolt::forms.section.options.compact')),
                        ]),
                    self::visibility($allSections),
                    Bolt::getCustomSchema('section') ?? [],
                ])),
        ];
    }

    public static function getMainFormSchema(): array
    {
        return [
            Hidden::make('user_id')->default(auth()->user()->id ?? null),

            Tabs::make('form-tabs')
                ->tabs(static::getTabsSchema())
                ->columnSpan(2),

            Repeater::make('sections')
                ->hiddenLabel()
                ->schema(static::getSectionsSchema())
                ->relationship()
                ->orderColumn('ordering')
                ->addActionLabel(__('zeus-bolt::forms.section.options.add'))
                ->cloneable()
                ->collapsible()
                ->collapsed(fn (string $operation) => $operation === 'edit')
                ->minItems(1)
                ->extraItemActions([
                    // @phpstan-ignore-next-line
                    Bolt::hasPro() ? \LaraZeus\BoltPro\Actions\SectionMarkAction::make('marks') : null,

                    Action::make('options')
                        ->label(__('zeus-bolt::forms.section.options.title'))
                        ->slideOver()
                        ->color('warning')
                        ->tooltip(__('zeus-bolt::forms.section.options.more'))
                        ->icon('heroicon-m-cog')
                        ->fillForm(fn (
                            array $arguments,
                            Repeater $component
                        ) => $component->getItemState($arguments['item']))
                        ->form(function (array $arguments, Get $get) {
                            $formOptions = $get('options');
                            $allSections = $get('sections');
                            unset($allSections[$arguments['item']]);

                            $allSections = self::getVisibleFields($allSections, $arguments);

                            return static::sectionOptionsFormSchema($formOptions, $allSections);
                        })
                        ->action(function (array $data, array $arguments, Repeater $component): void {
                            $state = $component->getState();
                            $state[$arguments['item']] = array_merge($state[$arguments['item']], $data);
                            $component->state($state);
                        }),
                ])
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->columnSpan(2),
        ];
    }

    public static function getTabsSchema(): array
    {
        $tabs = [
            Tabs\Tab::make('title-slug-tab')
                ->label(__('zeus-bolt::forms.options.tabs.title.label'))
                ->columns()
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->label(__('zeus-bolt::forms.options.tabs.title.name'))
                        ->afterStateUpdated(function (Set $set, $state, $context) {
                            if ($context === 'edit') {
                                return;
                            }
                            $set('slug', Str::slug($state));
                        }),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->rules(['alpha_dash'])
                        ->unique(ignoreRecord: true)
                        ->label(__('zeus-bolt::forms.options.tabs.title.slug')),

                    Select::make('category_id')
                        ->label(__('zeus-bolt::forms.options.tabs.title.category.label'))
                        ->searchable()
                        ->preload()
                        ->relationship(
                            'category',
                            'name',
                            modifyQueryUsing: function (Builder $query) {
                                if (Filament::getTenant() === null) {
                                    return $query;
                                }

                                return BoltPlugin::getModel('Category')::query()->whereBelongsTo(Filament::getTenant());
                            },
                        )
                        ->helperText(__('zeus-bolt::forms.options.tabs.title.category.hint'))
                        ->createOptionForm([
                            TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->label(__('zeus-bolt::forms.options.tabs.title.category.name'))
                                ->afterStateUpdated(function (Set $set, $state, $context) {
                                    if ($context === 'edit') {
                                        return;
                                    }
                                    $set('slug', Str::slug($state));
                                }),
                            TextInput::make('slug')
                                ->required()
                                ->maxLength(255)
                                ->label(__('zeus-bolt::forms.options.tabs.title.category.slug')),
                        ])
                        ->createOptionAction(fn (Action $action) => $action->hidden(auth()->user()->cannot('create', BoltPlugin::getModel('Category'))))
                        ->getOptionLabelFromRecordUsing(fn (Category $record) => $record->name),
                ]),

            Tabs\Tab::make('text-details-tab')
                ->label(__('zeus-bolt::forms.options.tabs.details.label'))
                ->schema([
                    Textarea::make('description')
                        ->label(__('zeus-bolt::forms.options.tabs.details.description'))
                        ->helperText(__('zeus-bolt::forms.options.tabs.details.description_help')),
                    RichEditor::make('details')
                        ->label(__('zeus-bolt::forms.options.tabs.details.details'))
                        ->helperText(__('zeus-bolt::forms.options.tabs.details.details_help')),
                    RichEditor::make('options.confirmation-message')
                        ->label(__('zeus-bolt::forms.options.tabs.details.confirmation_message'))
                        ->helperText(__('zeus-bolt::forms.options.tabs.details.confirmation_message_help')),
                ]),

            Tabs\Tab::make('display-access-tab')
                ->label(__('zeus-bolt::forms.options.tabs.display.label'))
                ->columns()
                ->schema([
                    Grid::make()
                        ->columnSpan(1)
                        ->columns(1)
                        ->schema([
                            Toggle::make('is_active')
                                ->label(__('zeus-bolt::forms.options.tabs.display.is_active'))
                                ->default(1)
                                ->helperText(__('zeus-bolt::forms.options.tabs.display.is_active_help')),
                            Toggle::make('options.require-login')
                                ->label(__('zeus-bolt::forms.options.tabs.display.require_login'))
                                ->helperText(__('zeus-bolt::forms.options.tabs.display.require_login_help'))
                                ->live(),
                            Toggle::make('options.one-entry-per-user')
                                ->label(__('zeus-bolt::forms.options.tabs.display.one_entry_per_user'))
                                ->helperText(__('zeus-bolt::forms.options.tabs.display.one_entry_per_user_help'))
                                ->visible(function (Get $get) {
                                    return $get('options.require-login');
                                }),
                        ]),
                    Grid::make()
                        ->columnSpan(1)
                        ->columns(1)
                        ->schema([
                            Radio::make('options.show-as')
                                ->label(__('zeus-bolt::forms.options.tabs.display.show_as.label'))
                                ->live()
                                ->default('page')
                                ->descriptions([
                                    'page' => __('zeus-bolt::forms.options.tabs.display.show_as.type_desc.page'),
                                    'wizard' => __('zeus-bolt::forms.options.tabs.display.show_as.type_desc.wizard'),
                                    'tabs' => __('zeus-bolt::forms.options.tabs.display.show_as.type_desc.tabs'),
                                ])
                                ->options([
                                    'page' => __('zeus-bolt::forms.options.tabs.display.show_as.type.page'),
                                    'wizard' => __('zeus-bolt::forms.options.tabs.display.show_as.type.wizard'),
                                    'tabs' => __('zeus-bolt::forms.options.tabs.display.show_as.type.tabs'),
                                ]),
                        ]),

                    TextInput::make('ordering')
                        ->numeric()
                        ->label(__('zeus-bolt::forms.options.tabs.display.ordering'))
                        ->default(1),
                ]),

            Tabs\Tab::make('advanced-tab')
                ->label(__('zeus-bolt::forms.options.tabs.advanced.label'))
                ->schema([
                    Grid::make()
                        ->columnSpanFull()
                        ->columns()
                        ->schema([
                            TextEntry::make('form-dates')
                                ->label(__('zeus-bolt::forms.options.tabs.advanced.dates'))
                                ->state(__('zeus-bolt::forms.options.tabs.advanced.dates_help'))
                                ->columnSpanFull(),
                            DateTimePicker::make('start_date')
                                ->requiredWith('end_date')
                                ->label(__('zeus-bolt::forms.options.tabs.advanced.start_date')),
                            DateTimePicker::make('end_date')
                                ->requiredWith('start_date')
                                ->label(__('zeus-bolt::forms.options.tabs.advanced.end_date')),
                        ]),
                    Grid::make()
                        ->columnSpanFull()
                        ->columns()
                        ->schema([
                            TextInput::make('options.emails-notification')
                                ->label(__('zeus-bolt::forms.options.tabs.advanced.emails_notifications'))
                                ->helperText(__('zeus-bolt::forms.options.tabs.advanced.emails_notifications_help')),
                        ]),
                ]),

            Tabs\Tab::make('extensions-tab')
                ->label(__('zeus-bolt::forms.options.tabs.extensions.label'))
                ->visible(BoltPlugin::get()->getExtensions() !== null)
                ->schema([
                    Select::make('extensions')
                        ->label(__('zeus-bolt::forms.options.tabs.extensions.label'))
                        ->preload()
                        ->live()
                        ->options(function () {
                            // @phpstan-ignore-next-line
                            return collect(BoltPlugin::get()->getExtensions())
                                ->mapWithKeys(function (string $item): array {
                                    if (class_exists($item)) {
                                        return [$item => (new $item)->label()];
                                    }

                                    return [$item => $item];
                                });
                        }),
                ]),

            Tabs\Tab::make('design')
                ->label(__('zeus-bolt::forms.options.tabs.design.label'))
                ->visible(Bolt::hasPro() && config('zeus-bolt.allow_design'))
                ->schema([
                    ViewField::make('options.primary_color')
                        ->hiddenLabel()
                        ->view('zeus::filament.components.color-picker'),
                    FileUpload::make('options.logo')
                        ->disk(config('zeus-bolt.uploadDisk'))
                        ->directory(config('zeus-bolt.uploadDirectory'))
                        ->visibility(config('zeus-bolt.uploadVisibility'))
                        ->image()
                        ->imageEditor()
                        ->label(__('zeus-bolt::forms.options.tabs.design.logo')),
                    FileUpload::make('options.cover')
                        ->disk(config('zeus-bolt.uploadDisk'))
                        ->directory(config('zeus-bolt.uploadDirectory'))
                        ->visibility(config('zeus-bolt.uploadVisibility'))
                        ->image()
                        ->imageEditor()
                        ->label(__('zeus-bolt::forms.options.tabs.design.cover')),
                ]),
        ];

        $customSchema = Bolt::getCustomSchema('form');

        if ($customSchema !== null) {
            $tabs[] = $customSchema;
        }

        return $tabs;
    }

    public static function getSectionsSchema(): array
    {
        return array_filter([
            TextInput::make('name')
                ->columnSpanFull()
                ->required()
                ->lazy()
                ->label(__('zeus-bolt::forms.section.name')),

            TextEntry::make('section-fields-placeholder')
                ->label(__('zeus-bolt::forms.section.name')),

            Repeater::make('fields')
                ->relationship()
                ->orderColumn('ordering')
                ->cloneable()
                ->minItems(1)
                ->cloneAction(fn (Action $action) => $action->action(function (Component $component, $arguments) {
                    $items = $component->getState();
                    $originalItem = $items[$arguments['item']];
                    $clonedItem = array_merge($originalItem, [
                        'name' => $originalItem['name'] . ' new',
                        'options' => array_merge($originalItem['options'], [
                            'htmlId' => $originalItem['options']['htmlId'] . Str::random(2),
                        ]),
                    ]);

                    $items[] = $clonedItem;
                    $component->state($items);

                    return $items;
                }))
                ->collapsible()
                ->collapsed(fn (string $operation) => $operation === 'edit')
                ->grid([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                    '2xl' => 3,
                ])
                ->hiddenLabel()
                ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                ->addActionLabel(__('zeus-bolt::forms.fields.add'))
                ->extraItemActions([
                    // @phpstan-ignore-next-line
                    Bolt::hasPro() ? \LaraZeus\BoltPro\Actions\FieldMarkAction::make('marks') : null,

                    Action::make('fields options')
                        ->slideOver()
                        ->color('warning')
                        ->tooltip('more field options')
                        ->icon('heroicon-m-cog')
                        ->modalIcon('heroicon-m-cog')
                        ->modalDescription(__('zeus-bolt::forms.fields.settings'))
                        ->fillForm(
                            fn (array $arguments, Repeater $component) => $component->getItemState($arguments['item'])
                        )
                        ->form(function (Get $get, array $arguments, Repeater $component) {
                            $allSections = self::getVisibleFields($get('../../sections'), $arguments);

                            return [
                                Textarea::make('description')
                                    ->label(__('zeus-bolt::forms.fields.description')),
                                Group::make()
                                    ->label(__('zeus-bolt::forms.fields.options'))
                                    ->schema(function (Get $get) use ($allSections, $component, $arguments) {
                                        $class = $get('type');
                                        if (class_exists($class)) {
                                            $newClass = (new $class);
                                            if ($newClass->hasOptions()) {
                                                return $newClass->getOptions($allSections, $component->getState()[$arguments['item']]);
                                            }
                                        }

                                        return [];
                                    }),
                            ];
                        })
                        ->action(function (array $data, array $arguments, Repeater $component): void {
                            $state = $component->getState();
                            $state[$arguments['item']] = array_merge($state[$arguments['item']], $data);
                            $component->state($state);
                        }),
                ])
                ->schema(static::getFieldsSchema()),

            Hidden::make('compact')->default(0)->nullable(),
            Hidden::make('aside')->default(0)->nullable(),
            Hidden::make('borderless')->default(0)->nullable(),
            Hidden::make('icon')->nullable(),
            Hidden::make('columns')->default(1)->nullable(),
            Hidden::make('description')->nullable(),
            Hidden::make('options.visibility.active')->default(0)->nullable(),
            Hidden::make('options.visibility.fieldID')->nullable(),
            Hidden::make('options.visibility.values')->nullable(),
            ...Bolt::getHiddenCustomSchema('section') ?? [],
        ]);
    }

    public static function getCleanOptionString(array $field): string
    {
        return
            view('zeus::filament.fields.types')
                ->with('field', $field)
                ->render();
    }

    public static function getFieldsSchema(): array
    {
        return [
            Hidden::make('description'),
            TextInput::make('name')
                ->required()
                ->lazy()
                ->label(__('zeus-bolt::forms.fields.name')),
            Select::make('type')
                ->required()
                ->searchable()
                ->preload()
                ->getSearchResultsUsing(function (string $search) {
                    return Bolt::availableFields()
                        ->filter(fn ($q) => str($q['title'])->contains($search))
                        ->mapWithKeys(fn ($field) => [$field['class'] => static::getCleanOptionString($field)])
                        ->toArray();
                })
                ->allowHtml()
                ->extraAttributes(['class' => 'field-type'])
                ->options(function (): array {
                    return Bolt::availableFields()
                        ->mapWithKeys(function ($field) {
                            return [$field['class'] => static::getCleanOptionString($field)];
                        })
                        ->toArray();
                })
                ->live()
                ->default(\LaraZeus\Bolt\Fields\Classes\TextInput::class)
                ->label(__('zeus-bolt::forms.fields.type')),
            Group::make()
                ->schema(function (Get $get) {
                    $class = $get('type');
                    if (class_exists($class)) {
                        $newClass = (new $class);
                        if ($newClass->hasOptions()) {
                            // @phpstan-ignore-next-line
                            return collect($newClass->getOptionsHidden())->flatten()->toArray();
                        }
                    }

                    return [];
                }),
        ];
    }
}
