<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ticket feature removed → drop the ticket_no column where it was added
    public function up(): void
    {
        if (Schema::hasColumn('registrations', 'ticket_no')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->dropColumn('ticket_no');
            });
        }
    }

    public function down(): void
    {
        // feature removed, nothing to restore
    }
};
