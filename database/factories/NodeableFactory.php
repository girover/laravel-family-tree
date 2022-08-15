<?php

namespace Girover\Tree\Database\Factories;

use Girover\Tree\Models\Nodeable;
use Illuminate\Database\Eloquent\Factories\Factory;


class NodeableFactory extends Factory
{
    protected $model = Nodeable::class;

    
    public function definition()
    {
        // $this->model =  ModelService::nodeModel();
        return [
            'name'        => $this->faker->name(),
            'father_name' => $this->faker->name(),
            'birth_date'  => $this->faker->date(),
        ];
    }
}
