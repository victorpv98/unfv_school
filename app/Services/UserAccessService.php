<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserAccessService
{
    public function createOptionalAccess(array $data, string $role, string $fallbackName): ?User
    {
        if (empty($data['create_user'])) {
            return null;
        }

        if (blank($data['user_email'] ?? null)) {
            throw ValidationException::withMessages([
                'user_email' => 'Ingrese el correo para crear el acceso.',
            ]);
        }

        $existing = User::where('email', $data['user_email'])->first();

        if ($existing) {
            if ($existing->role !== $role) {
                throw ValidationException::withMessages([
                    'user_email' => 'Ya existe un usuario con ese correo y tiene otro rol.',
                ]);
            }

            return $existing;
        }

        return User::create([
            'role' => $role,
            'name' => $data['user_name'] ?? $fallbackName,
            'email' => $data['user_email'],
            'password' => Hash::make($data['user_password'] ?? 'password'),
            'is_active' => true,
            'must_change_password' => true,
            'access_created_automatically' => true,
            'created_by' => Auth::id(),
        ]);
    }
}
