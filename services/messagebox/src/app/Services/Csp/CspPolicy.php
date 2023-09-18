<?php

declare(strict_types=1);

namespace App\Services\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policies\Policy;

use function config;

class CspPolicy extends Policy
{
    public function configure(): void
    {
        $this->addDirective(Directive::BASE, Keyword::SELF)
            ->addDirective(Directive::DEFAULT, Keyword::SELF)
            ->addDirective(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->addDirective(Directive::OBJECT, Keyword::NONE)
            ->addDirective(Directive::SCRIPT, Keyword::SELF)
            ->addDirective(Directive::STYLE, Keyword::SELF)
            ->addNonceForDirective(Directive::SCRIPT);

        if (config('app.debug')) {
            $this->addDirective(Directive::SCRIPT, Keyword::UNSAFE_EVAL);
        }
    }
}
