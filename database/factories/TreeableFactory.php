<?php

namespace Girover\Tree\Database\Factories;

use Girover\Tree\Models\Treeable;
use Illuminate\Database\Eloquent\Factories\Factory;


class TreeableFactory extends Factory
{
    protected $model = Treeable::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
