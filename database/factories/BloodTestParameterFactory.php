<?php

namespace Database\Factories;

use App\Models\BloodTestParameter;
use App\Models\LabTest;
use Illuminate\Database\Eloquent\Factories\Factory;

class BloodTestParameterFactory extends Factory
{
    protected $model = BloodTestParameter::class;

    public function definition(): array
    {
        $parameters = [
            'Hemoglobin' => ['unit' => 'g/dL', 'range' => '13.5-17.5', 'normal' => 15.0],
            'WBC Count' => ['unit' => 'cells/uL', 'range' => '4,500-11,000', 'normal' => 7000],
            'Platelets' => ['unit' => 'cells/uL', 'range' => '150,000-450,000', 'normal' => 250000],
            'Glucose' => ['unit' => 'mg/dL', 'range' => '70-99', 'normal' => 85],
            'Cholesterol' => ['unit' => 'mg/dL', 'range' => '125-200', 'normal' => 170],
        ];

        $name = $this->faker->randomElement(array_keys($parameters));
        $param = $parameters[$name];
        $value = $param['normal'] + $this->faker->numberBetween(-20, 20);
        $flag = 'Normal';

        if ($value > (float)explode('-', $param['range'])[1]) {
            $flag = 'High';
        } elseif ($value < (float)explode('-', $param['range'])[0]) {
            $flag = 'Low';
        }

        return [
            'lab_test_id' => LabTest::factory(),
            'parameter_name' => $name,
            'value' => $value,
            'unit' => $param['unit'],
            'reference_range' => $param['range'],
            'flag' => $flag,
        ];
    }
}
