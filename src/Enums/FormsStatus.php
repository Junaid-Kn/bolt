<?php

namespace LaraZeus\Bolt\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum FormsStatus: string implements HasColor, HasIcon, HasLabel
{
    case NEW = 'NEW';
    case OPEN = 'OPEN';
    case CLOSE = 'CLOSE';

    public function getLabel(): ?string
    {
        return __('zeus-bolt::forms.status_labels.' . $this->name);
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::NEW => 'success',
            self::OPEN => 'info',
            self::CLOSE => 'danger',
        };
    }

    public function getChartColors(): string | array | null
    {
        return [
            'NEW' => '#21C55D',
            'OPEN' => '#21C55D',
            'CLOSE' => '#EF4444',
        ];
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NEW => 'heroicon-o-document',
            self::OPEN => 'heroicon-o-x-circle',
            self::CLOSE => 'heroicon-o-lock-closed',
        };
    }
}
