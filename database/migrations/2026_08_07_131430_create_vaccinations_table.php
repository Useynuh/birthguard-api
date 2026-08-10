<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccinations', function (Blueprint $table) {
            $table->bigIncrements('vaccination_id');

            $table->foreignId('child_id')
                ->constrained('children')
                ->cascadeOnDelete();

            $table->string('vaccine_name');

            $table->date('date');

            $table->time('time');

            $table->boolean('is_administered')
                ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccinations');
    }
};