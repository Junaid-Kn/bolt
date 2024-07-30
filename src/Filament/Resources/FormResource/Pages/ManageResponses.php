<?php

namespace LaraZeus\Bolt\Filament\Resources\FormResource\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LaraZeus\Bolt\BoltPlugin;
use LaraZeus\Bolt\Filament\Actions\SetResponseStatus;
use LaraZeus\Bolt\Filament\Exports\ResponseExporter;
use LaraZeus\Bolt\Filament\Resources\FormResource;
use LaraZeus\Bolt\Models\Field;
use LaraZeus\Bolt\Models\Form;
use LaraZeus\Bolt\Models\Response;

/**
 * @property Form $record.
 */
class ManageResponses extends ManageRelatedRecords
{
    protected static string $resource = FormResource::class;

    protected static string $relationship = 'responses';

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    public function table(Table $table): Table
    {
        $getUserModel = config('auth.providers.users.model')::getBoltUserFullNameAttribute();

        $mainColumns = [
            ImageColumn::make('user.avatar')
                ->sortable(false)
                ->searchable(false)
                ->label(__('zeus-bolt::response.avatar'))
                ->circular()
                ->toggleable(),

            TextColumn::make('user.' . $getUserModel)
                ->label(__('zeus-bolt::response.name'))
                ->toggleable()
                ->sortable()
                ->default(__('zeus-bolt::response.guest'))
                ->searchable(),

            TextColumn::make('status')
                ->toggleable()
                ->sortable()
                ->badge()
                ->label(__('zeus-bolt::response.status'))
                ->formatStateUsing(fn ($state) => __(str($state)->title()->toString()))
                ->colors(BoltPlugin::getModel('FormsStatus')::pluck('key', 'color')->toArray())
                ->icons(BoltPlugin::getModel('FormsStatus')::pluck('key', 'icon')->toArray())
                ->grow(false)
                ->searchable('status'),

            TextColumn::make('notes')
                ->label(__('zeus-bolt::response.notes'))
                ->sortable()
                ->searchable()
                ->toggleable(),
        ];

        /**
         * @var Field $field.
         */
        foreach ($this->record->fields->sortBy('ordering') as $field) {
            $getFieldTableColumn = (new $field->type)->TableColumn($field);

            if ($getFieldTableColumn !== null) {
                $mainColumns[] = $getFieldTableColumn;
            }
        }

        $mainColumns[] = TextColumn::make('created_at')
            ->sortable()
            ->searchable()
            ->dateTime()
            ->label(__('zeus-bolt::response.notes'))
            ->toggleable();

        return $table
            ->query(
                BoltPlugin::getModel('Response')::query()
                    ->where('form_id', $this->record->id)
                    ->with(['fieldsResponses'])
                    ->withoutGlobalScopes([
                        SoftDeletingScope::class,
                    ])
            )
            ->columns($mainColumns)
            ->actions([
                SetResponseStatus::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from'),
                        DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\TrashedFilter::make(),
                SelectFilter::make('status')
                    ->options(BoltPlugin::getModel('FormsStatus')::query()->pluck('label', 'key'))
                    ->label(__('zeus-bolt::response.status')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),

                Tables\Actions\ExportBulkAction::make()
                    ->label(__('zeus-bolt::response.export_responses'))
                    ->exporter(ResponseExporter::class),
            ])
            ->recordUrl(
                fn (Response $record): string => FormResource::getUrl('viewResponse', [
                    'record' => $record->form->slug,
                    'responseID' => $record,
                ]),
            );
    }

    public static function getNavigationLabel(): string
    {
        return __('zeus-bolt::response.entries_report');
    }

    public function getTitle(): string
    {
        return __('zeus-bolt::response.entries_report');
    }
}
