<?php

namespace LaraZeus\Bolt;

use Closure;
use Filament\Support\Concerns\EvaluatesClosures;
use LaraZeus\Bolt\Filament\Resources\CategoryResource;
use LaraZeus\Bolt\Filament\Resources\CollectionResource;
use LaraZeus\Bolt\Filament\Resources\FormResource;
use LaraZeus\Core\Concerns\CanGloballySearch;
use LaraZeus\Core\Concerns\CanHideResources;
use LaraZeus\Core\Concerns\CanStickyActions;
use LaraZeus\Core\Concerns\HasModels;
use LaraZeus\Core\Concerns\HasNavigationBadges;
use LaraZeus\Core\Concerns\HasNavigationGroupLabel;
use LaraZeus\Core\Concerns\HasUploads;

trait Configuration
{
    use CanGloballySearch;
    use CanHideResources;
    use CanStickyActions;
    use EvaluatesClosures;
    use HasModels;
    use HasNavigationBadges;
    use HasNavigationGroupLabel;
    use HasUploads;

    public array $defaultGloballySearchableAttributes = [
        CategoryResource::class => ['name', 'slug'],
        CollectionResource::class => ['name', 'values'],
        FormResource::class => ['name', 'slug'],
    ];

    protected Closure | string $navigationGroupLabel = 'Bolt';

    protected array $customSchema = [
        'form' => null,
        'section' => null,
        'field' => null,
    ];

    /**
     * available extensions, leave it null to disable the extensions tab from the forms
     */
    protected ?array $extensions = null;

    public static function getDefaultModelsToMerge(): array
    {
        return config('zeus-bolt.models');
    }

    public function customSchema(array $schema): static
    {
        $this->customSchema = $schema;

        return $this;
    }

    public function getCustomSchema(): array
    {
        return $this->customSchema;
    }

    public static function getSchema(string $type): ?string
    {
        return (new static)::get()->getCustomSchema()[$type];
    }

    public function extensions(?array $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }

    public function getExtensions(): ?array
    {
        return $this->extensions;
    }
}
