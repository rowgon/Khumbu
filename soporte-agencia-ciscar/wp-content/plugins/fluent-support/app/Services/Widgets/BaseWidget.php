<?php

namespace FluentSupport\App\Services\Widgets;

use FluentSupport\Framework\Support\Arr;

abstract class BaseWidget
{
    abstract public function widgetName(): string;

    abstract public function widgetData(): array;

    public static function widgets(): array
    {
        $instance = new static();
        $stats = apply_filters(
            'fluent_support/widgets/' . $instance->widgetName(),
            $instance->widgetData()
        );
        return Arr::wrap($stats);
    }
}
