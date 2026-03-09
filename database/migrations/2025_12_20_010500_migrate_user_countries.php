<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('country_user')) {
            Schema::create('country_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('country_id')->constrained('countries')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['country_id', 'user_id']);
            });
        }

        if (Schema::hasColumn('users', 'pais_id')) {
            DB::table('users')
                ->whereNotNull('pais_id')
                ->orderBy('id')
                ->chunkById(500, function ($users) {
                    $rows = [];

                    foreach ($users as $user) {
                        $rows[] = [
                            'country_id' => $user->pais_id,
                            'user_id' => $user->id,
                            'is_primary' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (! empty($rows)) {
                        DB::table('country_user')->upsert(
                            $rows,
                            ['country_id', 'user_id'],
                            ['is_primary', 'updated_at']
                        );
                    }
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['pais_id']);
                $table->dropColumn('pais_id');
            });
        }

        if (Schema::hasColumn('companies', 'pais_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropForeign(['pais_id']);
                $table->dropColumn('pais_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('companies', 'pais_id')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreignId('pais_id')->nullable()->after('ruc')->constrained('countries')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('users', 'pais_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('pais_id')->nullable()->after('cargo')->constrained('countries')->nullOnDelete();
            });

            if (Schema::hasTable('country_user')) {
                DB::table('country_user')
                    ->orderBy('user_id')
                    ->chunkById(500, function ($rows) {
                        foreach ($rows as $row) {
                            DB::table('users')
                                ->where('id', $row->user_id)
                                ->whereNull('pais_id')
                                ->update([
                                    'pais_id' => $row->country_id,
                                ]);
                        }
                    });
            }
        }

        Schema::dropIfExists('country_user');
    }
};
