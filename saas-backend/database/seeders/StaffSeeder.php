<?php

namespace Database\Seeders;

use App\Models\Person;
use App\Models\Project;
use App\Models\Staff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $persons = Person::get();
        $project = Project::first();

        foreach ($persons as $person) {
            Staff::create([
                'project_id' => $project->id,
                'user_id' => $person->user_id,
                'person_id' => $person->id,
            ]);
        }
    }
}
