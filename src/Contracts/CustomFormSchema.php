<?php

namespace LaraZeus\Bolt\Contracts;

use Filament\Forms\Components\Tabs\Tab;

interface CustomFormSchema
{
    public function make(): Tab;
}
