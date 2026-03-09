<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->migrateRoleAssignments();
        $this->migrateRolePermissions();

        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
    }

    private function migrateRoleAssignments(): void
    {
        if (! Schema::hasTable('role_user') || ! Schema::hasTable('model_has_roles')) {
            return;
        }

        DB::table('role_user')
            ->orderBy('user_id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('model_has_roles')->updateOrInsert(
                        [
                            'role_id' => $row->role_id,
                            'model_id' => $row->user_id,
                            'model_type' => User::class,
                        ],
                        []
                    );
                }
            });
    }

    private function migrateRolePermissions(): void
    {
        if (! Schema::hasTable('permission_role') || ! Schema::hasTable('role_has_permissions')) {
            return;
        }

        DB::table('permission_role')
            ->orderBy('permission_id')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('role_has_permissions')->updateOrInsert(
                        [
                            'permission_id' => $row->permission_id,
                            'role_id' => $row->role_id,
                        ],
                        []
                    );
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_user')) {
            Schema::create('role_user', function (Blueprint $table) {
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['user_id', 'role_id']);
            });
        }

        if (! Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
                $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
                $table->timestamps();

                $table->primary(['permission_id', 'role_id']);
            });
        }
    }
};
