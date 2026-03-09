<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('roles', 'nombre')) {
            $this->dropSqlServerColumn('roles', 'nombre', ['roles_nombre_unique']);
        }

        if (Schema::hasColumn('roles', 'descripcion')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('descripcion');
            });
        }

        if (Schema::hasColumn('permissions', 'nombre')) {
            $this->dropSqlServerColumn('permissions', 'nombre', ['permissions_nombre_unique']);
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('roles', 'nombre')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('nombre', 120)->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('roles', 'descripcion')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->string('descripcion', 255)->nullable()->after('nombre');
            });
        }

        if (! Schema::hasColumn('permissions', 'nombre')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('nombre', 150)->nullable()->after('id');
            });
        }
    }
    private function dropSqlServerColumn(string $table, string $column, array $indexes = []): void
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            if (! empty($indexes)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        $table->dropUnique($index);
                    }
                });
            }

            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });

            return;
        }

        if ($driver !== 'sqlsrv') {
            Schema::table($table, function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });

            return;
        }

        $qualifiedTable = $connection->getTablePrefix() . $table;

        foreach ($indexes as $index) {
            $connection->statement(
                sprintf(
                    "IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = '%s' AND object_id = OBJECT_ID('%s')) DROP INDEX [%s] ON [%s]",
                    $index,
                    $qualifiedTable,
                    $index,
                    $qualifiedTable
                )
            );
        }

        $connection->statement(
            "DECLARE @sql NVARCHAR(MAX) = '';"
            . "SELECT @sql += 'ALTER TABLE " . $qualifiedTable . " DROP CONSTRAINT ' + OBJECT_NAME([default_object_id]) + ';'"
            . " FROM sys.columns"
            . " WHERE [object_id] = OBJECT_ID(N'" . $qualifiedTable . "') AND [name] = '" . $column . "' AND [default_object_id] <> 0;"
            . "EXEC(@sql);"
        );

        $connection->statement("ALTER TABLE [" . $qualifiedTable . "] DROP COLUMN [" . $column . "]");
    }
};
