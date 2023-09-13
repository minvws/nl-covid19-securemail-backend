<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\ConverterInterface;

class MarkdownServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ConverterInterface::class, function () {
            return new CommonMarkConverter([
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
                'max_nesting_level' => 10,
            ]);
        });
    }
}
