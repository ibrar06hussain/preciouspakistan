<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

class JoditEditor extends Field
{
    protected string $view = 'filament.forms.components.jodit-editor';

    protected int $minHeight = 260;

    public function minHeight(int $pixels): static
    {
        $this->minHeight = max(140, $pixels);

        return $this;
    }

    public function getMinHeight(): int
    {
        return $this->minHeight;
    }
}
