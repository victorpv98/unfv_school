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
            if (! Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('is_active');
            }

            if (! Schema::hasColumn('users', 'access_created_automatically')) {
                $table->boolean('access_created_automatically')->default(false)->after('must_change_password');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('access_created_automatically');
            }

            if (! Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('last_login_at')->constrained('users')->nullOnDelete();
            }
        });

        foreach (['students', 'guardians', 'teachers', 'enrollments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'created_by')) {
                    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn($tableName, 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                }
            });
        }

        Schema::table('student_guardian', function (Blueprint $table) {
            if (! Schema::hasColumn('student_guardian', 'status')) {
                $table->string('status', 30)->default('activo')->after('is_primary');
            }

            if (! Schema::hasColumn('student_guardian', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('course_teacher', function (Blueprint $table) {
            if (! Schema::hasColumn('course_teacher', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('course_teacher', function (Blueprint $table) {
                $table->dropUnique('course_teacher_unique');
                $table->unique(['academic_year_id', 'course_id', 'teacher_id', 'grade_id', 'section_id'], 'course_teacher_year_unique');
            });
        }

        if (Schema::hasTable('academic_years') && Schema::hasColumn('course_teacher', 'academic_year_id')) {
            $activeYearId = DB::table('academic_years')->where('status', 'activo')->orderByDesc('year')->value('id')
                ?? DB::table('academic_years')->orderByDesc('year')->value('id');

            if ($activeYearId) {
                DB::table('course_teacher')->whereNull('academic_year_id')->update(['academic_year_id' => $activeYearId]);
            }
        }

        Schema::table('payment_concepts', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_concepts', 'description')) {
                $table->text('description')->nullable()->after('name');
            }

            if (! Schema::hasColumn('payment_concepts', 'sort_order')) {
                $table->unsignedSmallInteger('sort_order')->default(0)->after('status');
            }
        });

        Schema::table('student_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('student_payments', 'enrollment_id')) {
                $table->foreignId('enrollment_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('student_payments', 'amount_paid')) {
                $table->decimal('amount_paid', 10, 2)->default(0)->after('amount');
            }

            if (! Schema::hasColumn('student_payments', 'due_date')) {
                $table->date('due_date')->nullable()->after('status');
            }

            if (! Schema::hasColumn('student_payments', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('paid_at');
            }

            if (! Schema::hasColumn('student_payments', 'paid_by_user_id')) {
                $table->foreignId('paid_by_user_id')->nullable()->after('payment_method')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('student_payments', 'registered_by')) {
                $table->foreignId('registered_by')->nullable()->after('paid_by_user_id')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('student_payments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('observations');
            }

            if (! Schema::hasColumn('student_payments', 'cancelled_reason')) {
                $table->text('cancelled_reason')->nullable()->after('cancelled_at');
            }
        });

        if (Schema::hasColumn('users', 'role_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('users_role_id_index');
                $table->dropColumn('role_id');
            });
        }

        Schema::dropIfExists('roles');
    }

    public function down(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role_id')) {
                $table->foreignId('role_id')->nullable()->index()->after('id');
            }
        });
    }
};
