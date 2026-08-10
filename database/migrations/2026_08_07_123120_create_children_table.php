<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table) {
            $table->id();

            // Parent who owns/registered the child
            $table->foreignId('parent_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Birth information
            $table->string('full_name');
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('place_of_birth');

            // Birth measurements
            $table->decimal('weight_at_birth', 5, 2);
            $table->decimal('height_at_birth', 5, 2);

            // Preserve the old BirthGuard concept of registered_by
            $table->string('registered_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};