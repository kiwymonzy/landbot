<?php

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class CollectionMacroProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerMacros();
    }

    public function registerMacros()
    {
        Collection::macro('recursive', function () {
            return $this->map(function ($value) {
                return is_array($value) || is_object($value)
                ? (new static($value))->recursive()
                : $value;
            });
        });
    }
}
