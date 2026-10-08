<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('incident_id')->constrained('incidents')->cascadeOnDelete();
            $table->string('author_name');
            $table->text('body');
            $table->timestamp('created_at', 6)->useCurrent();
            $table->timestamp('updated_at', 6)->nullable();

            $table->index(['incident_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_comments');
    }
};
