<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Services\EmployeeCodeGenerator;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $numberToCreate = 50;

        $employeeCodes = $this->generateUniqueEmployeeCodes($numberToCreate);

        Employee::factory($numberToCreate)
            ->sequence(function () use (&$employeeCodes) {
                return [
                    "code" => $employeeCodes->pop(),
                ];
            })
            ->create();
    }

    /**
     * Generate unique employee codes.
     *
     * @param int $count
     * @return \Illuminate\Support\Collection
     */
    private function generateUniqueEmployeeCodes($count)
    {
        $employeeCodes = collect([]);

        $alreadyInDbEmployeeCodes = Employee::pluck("code");

        $i = 0;
        while ($i < $count) {
            $code = EmployeeCodeGenerator::generate();
            if (!$employeeCodes->contains($code) && !$alreadyInDbEmployeeCodes->contains($code)) {
                $employeeCodes->add($code);
                $i++;
            }
        }

        return $employeeCodes;
    }
}
