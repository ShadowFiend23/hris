<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rename the payroll-period "processing" status to "review" (displayed as "For Review").
     * On SQL Server the original enum CHECK constraint must be dropped first so the new value
     * is allowed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('payroll_periods')) {
            return;
        }

        if (DB::getDriverName() === 'sqlsrv') {
            // Drop every CHECK constraint on the status column (matched by definition, since
            // SQL Server truncates the auto-generated constraint name).
            DB::statement("
                DECLARE @sql NVARCHAR(MAX) = N''
                SELECT @sql = @sql + 'ALTER TABLE payroll_periods DROP CONSTRAINT [' + cc.name + '];'
                FROM sys.check_constraints cc
                JOIN sys.tables t ON cc.parent_object_id = t.object_id
                WHERE t.name = 'payroll_periods' AND cc.definition LIKE '%status%'
                IF @sql <> N'' EXEC(@sql)
            ");
        }

        DB::table('payroll_periods')->where('status', 'processing')->update(['status' => 'review']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('payroll_periods')) {
            return;
        }

        DB::table('payroll_periods')->where('status', 'review')->update(['status' => 'processing']);
    }
};
