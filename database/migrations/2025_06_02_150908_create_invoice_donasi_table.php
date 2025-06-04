<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoice_donasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('donasi_id')->constrained('donasis')->onDelete('cascade');
            $table->string('nomor_invoice', 50)->unique();
            
            $table->enum('delivery_method', ['email', 'whatsapp', 'sms', 'download', 'print']);
            $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            
            $table->string('sent_to_email')->nullable();
            $table->string('sent_to_phone', 20)->nullable();
            $table->text('delivery_notes')->nullable();
            
            $table->string('pdf_file_path', 500);
            $table->foreignId('created_by_user_id')->constrained('users');
            
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            $table->index(['donasi_id', 'delivery_status']);
            $table->index('nomor_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_donasi');
    }
};
