<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $companyCount = Company::count();
        for ($i = $companyCount + 1; $i <= 10; $i++) {
          Company::create([
                'company_name' => 'Company ' . $i,
                'company_description' => 'Company Description ' .$i,
            ]);
        }
    }
}
