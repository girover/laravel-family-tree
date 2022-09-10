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
            gender()      => $this->faker->randomElement([male(),female()]),
            'b_date'  => $this->faker->date(),
        ];
    }

    public function male()
    {
        return $this->state(function (array $attributes) {
            return [
                gender() => male(),
            ];
        });
    }

    public function female()
    {
        return $this->state(function (array $attributes) {
            return [
                gender() => female(),
            ];
        });
    }
}
