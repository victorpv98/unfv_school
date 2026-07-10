<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    /**
     * @var array<int, array{email: string, role: string, name: string}>
     */
    private array $users = [
        ['email' => 'admin@school.test', 'role' => 'administrador', 'name' => 'Administrador Demo'],
        ['email' => 'secretaria@school.test', 'role' => 'secretaria', 'name' => 'Secretaria Demo'],
        ['email' => 'docente@school.test', 'role' => 'docente', 'name' => 'Docente Demo'],
        ['email' => 'alumno@school.test', 'role' => 'alumno', 'name' => 'Alumno Demo'],
        ['email' => 'apoderado@school.test', 'role' => 'apoderado', 'name' => 'Apoderado Demo'],
    ];

    public function run(): void
    {
        foreach ($this->users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'role' => $user['role'],
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'must_change_password' => false,
                    'access_created_automatically' => false,
                ]
            );
        }
    }
}
