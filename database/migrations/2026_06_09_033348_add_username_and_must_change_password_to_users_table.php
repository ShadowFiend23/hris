<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add columns without unique constraint first (SQL Server rejects multi-NULL unique indexes)
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->after('name');
            $table->boolean('must_change_password')->default(false)->after('remember_token');
        });

        // Backfill usernames for existing users using first-initial.lastname format
        $used = [];
        User::query()->orderBy('id')->each(function (User $user) use (&$used): void {
            $parts = explode(' ', trim($user->name));
            $firstInitial = strtolower(mb_substr($parts[0], 0, 1));
            $lastNameParts = array_slice($parts, 1);
            $lastName = strtolower(implode('', array_map(fn ($p) => preg_replace('/[^a-z0-9]/', '', strtolower($p)), $lastNameParts)));

            if ($lastName === '') {
                $lastName = strtolower(preg_replace('/[^a-z0-9]/', '', $parts[0]));
            }

            $base = $firstInitial.'.'.$lastName;
            $username = $base;
            $counter = 2;
            while (in_array($username, $used)) {
                $username = $base.$counter;
                $counter++;
            }
            $used[] = $username;
            $user->updateQuietly(['username' => $username]);
        });

        // Now safe to add the unique index — all rows have a value
        Schema::table('users', function (Blueprint $table): void {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_username_unique');
            $table->dropColumn(['username', 'must_change_password']);
        });
    }
};
