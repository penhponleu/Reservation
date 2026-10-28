<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ticket types removed → drop registrations.ticket_type_id and the ticket_types table
    public function up(): void
    {
        if (Schema::hasColumn('registrations', 'ticket_type_id')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->dropConstrainedForeignId('ticket_type_id');
            });
        }
        Schema::dropIfExists('ticket_types');
    }

    public function down(): void
    {
        // feature removed, nothing to restore
    }
};
