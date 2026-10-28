<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ticket number (TKT-000001); null until admin clicks "Get ticket"
    public function up(): void
    {
        if (! Schema::hasColumn('registrations', 'ticket_no')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->string('ticket_no', 20)->nullable()->unique()->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(['ticket_no']);
            $table->dropColumn('ticket_no');
        });
    }
};
