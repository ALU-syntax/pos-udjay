<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'admin',
            'username' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'role' => 1,
            'outlet_id' => json_encode([1])
        ])->assignRole('admin');

        User::create([
            'name' => 'ardian',
            'username' => 'ardian',
            'email' => 'ardianiqbal40@gmail.com',
            'password' => bcrypt('password'),
            'status' => 1,
            'role' => 1,
            'outlet_id' => json_encode([1, 2])
        ])->assignRole('admin');
    }
}
