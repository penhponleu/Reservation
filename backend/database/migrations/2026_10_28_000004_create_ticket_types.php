<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Ticket types (General, VIP...) - each registration (= ticket) has one
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_types')) {
            Schema::create('ticket_types', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50)->unique();
                $table->integer('quota')->nullable();          // null = no limit
                $table->boolean('is_active')->default(true);   // inactive = can't be given to guests
                $table->timestamps();
            });
        }

        foreach ([['General', null], ['VIP', 50]] as [$name, $quota]) {
            if (! DB::table('ticket_types')->where('name', $name)->exists()) {
                DB::table('ticket_types')->insert(['name' => $name, 'quota' => $quota, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        if (! Schema::hasColumn('registrations', 'ticket_type_id')) {
            Schema::table('registrations', function (Blueprint $table) {
                $table->foreignId('ticket_type_id')->nullable()->constrained('ticket_types')->nullOnDelete();
            });
        }

        // guests registered before this → General
        $general = DB::table('ticket_types')->where('name', 'General')->value('id');
        DB::table('registrations')->whereNull('ticket_type_id')->update(['ticket_type_id' => $general]);
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ticket_type_id');
        });
        Schema::dropIfExists('ticket_types');
    }
};
