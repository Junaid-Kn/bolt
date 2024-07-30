<?php

namespace LaraZeus\Bolt\Fields\Classes;

use Filament\Forms\Components\Hidden;
use LaraZeus\Accordion\Forms\Accordion;
use LaraZeus\Accordion\Forms\Accordions;
use LaraZeus\Bolt\Facades\Bolt;
use LaraZeus\Bolt\Fields\FieldsContract;
use LaraZeus\Bolt\Models\Field;
use LaraZeus\Bolt\Models\FieldResponse;

class FileUpload extends FieldsContract
{
    public string $renderClass = \Filament\Forms\Components\FileUpload::class;

    public int $sort = 11;

    public function icon(): string
    {
        return 'tabler-cloud-upload';
    }

    public static function getOptions(?array $sections = null): array
    {
        return [
            Accordions::make('check-list-options')
                ->accordions([
                    Accordion::make('general-options')
                        ->label(__('zeus-bolt::forms.fields.options.general'))
                        ->icon('iconpark-checklist-o')
                        ->schema([
                            \Filament\Forms\Components\Toggle::make('options.allow_multiple')
                                ->label(__('zeus-bolt::forms.fields.options.allow_multiple')),
                            self::isActive(),
                            self::required(),
                            self::columnSpanFull(),
                            self::htmlID(),
                        ]),
                    self::hintOptions(),
                    self::visibility($sections),
                    Bolt::getCustomSchema('field', resolve(static::class)) ?? [],
                ]),
        ];
    }

    public static function getOptionsHidden(): array
    {
        return [
            self::hiddenIsActive(),
            ...Bolt::getHiddenCustomSchema('field', resolve(static::class)) ?? [],
            self::hiddenHtmlID(),
            self::hiddenHintOptions(),
            self::hiddenRequired(),
            self::hiddenColumnSpanFull(),
            self::hiddenVisibility(),
            Hidden::make('options.allow_multiple')->default(false),
        ];
    }

    public function getResponse(Field $field, FieldResponse $resp): string
    {
        $responseValue = filled($resp->response) ? Bolt::isJson($resp->response) ? json_decode($resp->response) : [$resp->response] : [];

        return view('zeus::filament.fields.file-upload')
            ->with('resp', $resp)
            ->with('responseValue', $responseValue)
            ->with('field', $field)
            ->render();
    }

    public function TableColumn(Field $field): ?\Filament\Tables\Columns\Column
    {
        return null;
    }

    // @phpstan-ignore-next-line
    public function appendFilamentComponentsOptions($component, $zeusField, bool $hasVisibility = false)
    {
        parent::appendFilamentComponentsOptions($component, $zeusField, $hasVisibility);

        $component->disk(config('zeus-bolt.uploadDisk'))
            ->directory(config('zeus-bolt.uploadDirectory'))
            ->visibility(config('zeus-bolt.uploadVisibility'));

        if (isset($zeusField->options['allow_multiple']) && $zeusField->options['allow_multiple']) {
            $component = $component->multiple();
        }

        return $component;
    }
}
