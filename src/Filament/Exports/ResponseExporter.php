<?php

namespace LaraZeus\Bolt\Filament\Exports;

use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Model;
use LaraZeus\Bolt\Models\Field;
use LaraZeus\Bolt\Models\Response;

class ResponseExporter extends Exporter
{
    protected static ?string $model = Response::class;

    protected ?Model $record;

    public static function getColumns(): array
    {
        $record = \Livewire\Livewire::current()->getRecord();
        $getUserModel = config('auth.providers.users.model')::getBoltUserFullNameAttribute();
        $mainColumns = [
            ExportColumn::make('user.' . $getUserModel)
                ->label(__('zeus-bolt::response.name'))
                ->default(__('zeus-bolt::response.guest')),

            ExportColumn::make('status')
                ->label(__('zeus-bolt::response.status')),

            ExportColumn::make('notes')
                ->label(__('zeus-bolt::response.notes')),
        ];

        /**
         * @var Field $field.
         */
        foreach ($record->fields->sortBy('ordering') as $field) {
            $getFieldTableColumn = (new $field->type)->ExportColumn($field);

            if ($getFieldTableColumn !== null) {
                $mainColumns[] = $getFieldTableColumn;
            }
        }

        $mainColumns[] = ExportColumn::make('created_at')
            ->label(__('zeus-bolt::response.created_at'));

        return $mainColumns;
    }

    /*public static function getOptionsFormComponents(): array
    {
        return [
            TextInput::make('descriptionLimit')
                ->label('Limit the length of the description column content')
                ->integer(),
        ];
    }*/

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your response export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
