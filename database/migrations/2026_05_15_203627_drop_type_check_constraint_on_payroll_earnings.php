<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Only applies to SQL Server — SQLite (used in tests) has no named CHECK constraints.
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        if (Schema::hasTable('payroll_earnings')) {
            DB::statement("
                DECLARE @constraint NVARCHAR(256)
                SELECT @constraint = cc.name
                FROM sys.check_constraints cc
                JOIN sys.tables t ON cc.parent_object_id = t.object_id
                WHERE t.name = 'payroll_earnings' AND cc.name LIKE 'CK%type%'
                IF @constraint IS NOT NULL
                    EXEC('ALTER TABLE payroll_earnings DROP CONSTRAINT [' + @constraint + ']')
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlsrv') {
            return;
        }

        DB::statement("
            ALTER TABLE payroll_earnings ADD CONSTRAINT CK_payroll_earnings_type
            CHECK ([type] IN (
                'basic','overtime','overtime_weekday','overtime_weekend','overtime_holiday',
                'night_differential','regular_holiday','special_holiday','rest_day',
                'thirteenth_month','allowance','other'
            ))
        ");
    }
};
