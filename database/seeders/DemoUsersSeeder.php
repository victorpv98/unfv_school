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
        ['email' => 'admin@school.com', 'role' => 'administrador', 'name' => 'Administrador'],
        ['email' => 'secretaria@school.com', 'role' => 'secretaria', 'name' => 'Secretaria'],
        ['email' => 'docente@school.com', 'role' => 'docente', 'name' => 'Docente'],
        ['email' => 'alumno@school.com', 'role' => 'alumno', 'name' => 'Alumno'],
        ['email' => 'apoderado@school.com', 'role' => 'apoderado', 'name' => 'Apoderado'],
    ];

    public function run(): void
    {
        foreach ($this->users as $user) {
            $oldEmail = str_replace('@school.com', '@school.test', $user['email']);
            $newExists = User::where('email', $user['email'])->exists();

            if (! $newExists) {
                User::where('email', $oldEmail)->update(['email' => $user['email']]);
            }
        }

        foreach ($this->users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'role' => $user['role'],
                    'name' => $user['name'],
                    'password' => Hash::make('123456'),
                    'is_active' => true,
                    'must_change_password' => false,
                    'access_created_automatically' => false,
                ]
            );
        }
    }
}
