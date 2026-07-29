<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Field;

class Map extends Field
{
    protected string $view = 'forms.components.map';

    protected array|Closure $defaultLocation = [30.0444, 31.2357];

    protected bool|Closure $showMarker = true;

    protected bool|Closure $clickable = true;

    protected bool|Closure $draggable = true;

    protected int|Closure $zoom = 8;

    protected string|Closure|null $tilesUrl = null;

    public function defaultLocation(float $latitude, float $longitude): static
    {
        $this->defaultLocation = [$latitude, $longitude];

        return $this;
    }

    public function getDefaultLocation(): array
    {
        return $this->evaluate($this->defaultLocation);
    }

    public function showMarker(bool|Closure $condition): static
    {
        $this->showMarker = $condition;

        return $this;
    }

    public function getShowMarker(): bool
    {
        return $this->evaluate($this->showMarker);
    }

    public function clickable(bool|Closure $condition): static
    {
        $this->clickable = $condition;

        return $this;
    }

    public function getClickable(): bool
    {
        return $this->evaluate($this->clickable);
    }

    public function draggable(bool|Closure $condition): static
    {
        $this->draggable = $condition;

        return $this;
    }

    public function getDraggable(): bool
    {
        return $this->evaluate($this->draggable);
    }

    public function zoom(int|Closure $zoom): static
    {
        $this->zoom = $zoom;

        return $this;
    }

    public function getZoom(): int
    {
        return $this->evaluate($this->zoom);
    }

    public function tilesUrl(string|Closure|null $url): static
    {
        $this->tilesUrl = $url;

        return $this;
    }

    public function getTilesUrl(): ?string
    {
        return $this->evaluate($this->tilesUrl);
    }
}
