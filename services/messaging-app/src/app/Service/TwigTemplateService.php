<?php

declare(strict_types=1);

namespace MinVWS\MessagingApp\Service;

use Twig\Environment;

class TwigTemplateService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function render(string $name, array $context = []): string
    {
        return $this->twig->render($name, $context);
    }
}
