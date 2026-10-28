<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Login saves a row here (Laravel Sanctum). If the table was deleted, or made
// from an old import.sql without some columns, login fails with "Server Error".
// This puts back whatever is missing; it changes nothing if the table is fine.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('personal_access_tokens')) {
            Schema::create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->text('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable()->index();
                $table->timestamps();
            });

            return;
        }

        $missing = fn (string $column) => ! Schema::hasColumn('personal_access_tokens', $column);

        Schema::table('personal_access_tokens', function (Blueprint $table) use ($missing) {
            if ($missing('abilities')) {
                $table->text('abilities')->nullable();
            }
            if ($missing('last_used_at')) {
                $table->timestamp('last_used_at')->nullable();
            }
            if ($missing('expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
            if ($missing('created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        // repair only, nothing to undo
    }
};
