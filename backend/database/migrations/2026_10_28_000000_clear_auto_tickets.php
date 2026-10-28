<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Remove tickets made automatically at registration → admin gives them with "Get ticket"
    public function up(): void
    {
        DB::table('registrations')->whereNotNull('ticket_no')->update(['ticket_no' => null]);
    }

    public function down(): void
    {
        // nothing to restore: "Get ticket" makes the same number again (TKT + id)
    }
};
