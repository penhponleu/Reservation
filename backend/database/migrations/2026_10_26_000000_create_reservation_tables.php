<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// registrations, email_logs, checkin_logs - same columns as import.sql
// each table is skipped if import.sql already created it
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('registrations')) {
            Schema::create('registrations', function (Blueprint $table) {
                $table->id();
                $table->string('fullname', 100)->default('');   // guest full name
                $table->string('email')->unique();
                $table->string('phone', 30);
                $table->char('token', 36)->unique();             // value inside the QR code
                $table->timestamp('email_sent_at')->nullable();  // null = not sent
                $table->timestamp('checked_in_at')->nullable();  // null = not arrived
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
                $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete(); // null = system
                $table->enum('type', ['qr', 'resend']);
                $table->enum('status', ['sent', 'failed']);
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('checkin_logs')) {
            Schema::create('checkin_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('registration_id')->nullable()->constrained()->nullOnDelete(); // null = invalid QR
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();                  // staff who scanned
                $table->enum('result', ['success', 'already_checked_in', 'invalid']);
                $table->string('scanned_value');
                $table->timestamp('scanned_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('checkin_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('registrations');
    }
};
