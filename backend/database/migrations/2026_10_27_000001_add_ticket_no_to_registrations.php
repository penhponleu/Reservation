<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Ticket number per registration (TKT-000001), shown to the guest
    public function up(): void
    {
        if (! Schema::hasColumn('registrations', 'ticket_no')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->string('ticket_no', 20)->nullable()->unique()->after('id');
            });
        }

        // guests registered before this change get a ticket too
        foreach (DB::table('registrations')->whereNull('ticket_no')->pluck('id') as $id) {
            DB::table('registrations')->where('id', $id)->update(['ticket_no' => sprintf('TKT-%06d', $id)]);
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
