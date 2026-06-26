<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 30)->default('alumno')->after('role_id')->index();
            }
        });

        if (Schema::hasTable('roles') && Schema::hasColumn('users', 'role_id')) {
            DB::table('roles')
                ->select(['id', 'name'])
                ->orderBy('id')
                ->get()
                ->each(function (object $role): void {
                    DB::table('users')
                        ->where('role_id', $role->id)
                        ->update(['role' => $role->name]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }
};
