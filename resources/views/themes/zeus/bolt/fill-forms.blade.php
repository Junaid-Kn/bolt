@php
    $colors = \Illuminate\Support\Arr::toCssStyles([
        \Filament\Support\get_color_css_variables($zeusForm->options['primary_color'] ?? 'primary', shades: [50, 100, 200, 300, 400, 500, 600, 700, 800, 900]),
    ]);
@endphp

<style>
    /* Force orange colors everywhere - override everything */
    .fi-btn-primary,
    .fi-btn-primary:not(:disabled):hover,
    .fi-btn-primary:not(:disabled):focus {
        background-color: rgb(249 115 22) !important;
        border-color: rgb(249 115 22) !important;
        color: white !important;
    }
    
    .fi-input:focus,
    .fi-input:focus-within {
        border-color: rgb(249 115 22) !important;
        box-shadow: 0 0 0 1px rgb(249 115 22) !important;
    }
    
    .fi-checkbox:checked,
    .fi-checkbox[checked] {
        background-color: rgb(249 115 22) !important;
        border-color: rgb(249 115 22) !important;
    }
    
    .fi-radio:checked,
    .fi-radio[checked] {
        background-color: rgb(249 115 22) !important;
        border-color: rgb(249 115 22) !important;
    }
    
    .fi-select:focus {
        border-color: rgb(249 115 22) !important;
        box-shadow: 0 0 0 1px rgb(249 115 22) !important;
    }
    
    /* Override any custom colors */
    [style*="--primary-500"] {
        --primary-500: rgb(249 115 22) !important;
    }
    
    /* Hide any branding elements */
    .branding-section,
    .logo-section,
    .cover-section {
        display: none !important;
    }
</style>

<div class="not-prose max-w-4xl mx-auto p-6" style="{{ $colors }}">

    @if(!$inline)
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-3">{{ $zeusForm->name ?? '' }}</h2>
            <p class="text-gray-600 dark:text-gray-400 text-lg mb-4">{{ $zeusForm->description ?? '' }}</p>

            @if($zeusForm->start_date !== null)
                <div class="text-gray-500 dark:text-gray-400 text-sm flex items-center gap-2 bg-gray-50 dark:bg-gray-800 p-3 rounded-lg">
                    @svg('heroicon-o-calendar','h-5 w-5')
                    <span>{{ __('Available from') }}:</span>
                    <span class="font-medium">{{ optional($zeusForm->start_date)->format(\Filament\Infolists\Infolist::$defaultDateDisplayFormat) }}</span>
                    <span>{{ __('to') }}:</span>
                    <span class="font-medium">{{ optional($zeusForm->end_date)->format(\Filament\Infolists\Infolist::$defaultDateDisplayFormat) }}</span>
                </div>
            @endif
        </div>

        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm">
                @if($zeusForm->extensions === null)
                    <li class="flex items-center">
                        <a href="{{ route('bolt.forms.list') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium">{{ __('Forms') }}</a>
                        @svg('heroicon-s-arrow-small-right','fill-current w-4 h-4 mx-3 rtl:rotate-180 text-gray-400')
                    </li>
                @else
                    <li class="flex items-center">
                        <a href="{{ \LaraZeus\Bolt\Facades\Extensions::init($zeusForm, 'route') }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-700 dark:hover:text-orange-300 font-medium">{{ \LaraZeus\Bolt\Facades\Extensions::init($zeusForm, 'label') }}</a>
                        @svg('heroicon-s-arrow-small-right','fill-current w-4 h-4 mx-3 rtl:rotate-180 text-gray-400')
                    </li>
                @endif
                <li class="flex items-center text-gray-600 dark:text-gray-400 font-medium">
                    {{ $zeusForm->name }}
                </li>
            </ol>
        </nav>
    @endif

    @if(!$inline)
        @include($boltTheme.'.loading')
    @endif

    {{-- NO BRANDING SECTION - COMPLETELY REMOVED --}}

    @if($sent)
        @include($boltTheme.'.submitted')
    @else
        <x-filament-panels::form wire:submit.prevent="store" class="space-y-6">
            @if(!$inline)
                {{ \LaraZeus\Bolt\Facades\Bolt::renderHookBlade('zeus-form.before') }}
            @endif

            {!! \LaraZeus\Bolt\Facades\Extensions::init($zeusForm, 'render',$extensionData) !!}

            @if(!empty($zeusForm->details))
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Form Details</h3>
                    <div class="prose dark:prose-invert max-w-none text-gray-700 dark:text-gray-300">
                        {!! nl2br($zeusForm->details) !!}
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Form Fields</h3>
                {{ $this->form }}
            </div>

            <div class="flex justify-center pt-6">
                <x-filament::button
                    form="store"
                    type="submit"
                    :color="$zeusForm->options['primary_color'] ?? 'primary'"
                >
                    {{ __('Save') }}
                </x-filament::button>
            </div>

            @if(!$inline)
                {{ \LaraZeus\Bolt\Facades\Bolt::renderHookBlade('zeus-form.after') }}
            @endif
        </x-filament-panels::form>

        <x-filament-actions::modals/>
    @endif
</div>