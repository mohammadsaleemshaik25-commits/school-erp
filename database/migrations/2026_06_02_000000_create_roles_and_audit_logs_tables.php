<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->bigIncrements('role_id');
                $table->string('role_name', 50)->unique();
                $table->string('description')->nullable();
            });
        }

        $roles = ['Administrator', 'Principal', 'Correspondent', 'Clerk'];
        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['role_name' => $role],
                ['description' => $role.' role']
            );
        }

        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'user_id')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->renameColumn('id', 'user_id');
                });
            }

            if (! Schema::hasColumn('users', 'username')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->string('username', 100)->nullable()->unique()->after('user_id');
                });
            }

            if (! Schema::hasColumn('users', 'full_name')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->string('full_name')->nullable()->after('username');
                });
            }

            if (! Schema::hasColumn('users', 'password_hash')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->string('password_hash')->nullable()->after('email');
                });
            }

            if (! Schema::hasColumn('users', 'role_id')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->unsignedBigInteger('role_id')->nullable()->after('password_hash');
                });
            }

            if (! Schema::hasColumn('users', 'is_active')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->boolean('is_active')->default(true)->after('role_id');
                });
            }
        }

        if (Schema::hasColumn('users', 'role_id')) {
            try {
                Schema::table('users', function (Blueprint $table): void {
                    $table->foreign('role_id')->references('role_id')->on('roles');
                });
            } catch (\Throwable) {
                // Skip when the foreign key is already present.
            }
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table): void {
                $table->bigIncrements('audit_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('action', 120);
                $table->string('table_name', 120)->nullable();
                $table->string('record_id', 120)->nullable();
                $table->longText('old_value')->nullable();
                $table->longText('new_value')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('user_id')->references('user_id')->on('users');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('audit_logs')) {
            Schema::dropIfExists('audit_logs');
        }

        if (Schema::hasTable('roles')) {
            Schema::dropIfExists('roles');
        }
    }
};
