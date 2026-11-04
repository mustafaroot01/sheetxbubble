<?php

namespace Database\Seeders;

use App\Models\Stage;
use App\Models\StudyType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $teacherRole = Role::create(['name' => 'teacher']);

        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => bcrypt('password'),
        ]);
        $admin->assignRole('admin');

        // Create a sample teacher
        $teacher = User::create([
            'name' => 'Teacher',
            'email' => 'teacher@teacher.com',
            'password' => bcrypt('password'),
        ]);
        $teacher->assignRole('teacher');

        // Create stages
        Stage::create(['name' => 'First']);
        Stage::create(['name' => 'Second']);
        Stage::create(['name' => 'Third']);
        Stage::create(['name' => 'Fourth']);

        // Create study types
        StudyType::create(['name' => 'Morning']);
        StudyType::create(['name' => 'Evening']);
    }
}
