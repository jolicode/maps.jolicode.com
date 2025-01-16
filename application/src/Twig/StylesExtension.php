<?php

namespace App\Twig;

use App\Map\Styles;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class StylesExtension extends AbstractExtension
{
    public function __construct(
        private readonly Styles $styles,
    ) {
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('defaultStyle', $this->defaultStyle(...)),
        ];
    }

    public function defaultStyle(string $schema): string
    {
        return $this->styles->getDefaultStyle($schema);
    }
}
