<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('PRESCRIPTION')) {
            return;
        }

        Schema::create('PRESCRIPTION', function (Blueprint $table): void {
            $table->string('id_prescription', 42)->primary();
            $table->string('id_patient', 42);
            $table->string('id_docteur', 42);
            $table->string('id_etablissement', 42);
            $table->string('medicament', 150);
            $table->string('dosage', 100)->nullable();
            $table->unsignedSmallInteger('duree_jours')->nullable();
            $table->string('frequence', 100)->nullable();
            $table->unsignedSmallInteger('quantite')->nullable();
            $table->date('date_prescription');
            $table->timestamps();

            $table->foreign('id_patient')->references('id_patient')->on('PATIENT')->cascadeOnDelete();
            $table->foreign('id_docteur')->references('id_docteur')->on('DOCTEUR')->cascadeOnDelete();
            $table->foreign('id_etablissement')->references('id_etablissement')->on('ETABLISSEMENT')->cascadeOnDelete();
            $table->index(['id_patient', 'date_prescription']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('PRESCRIPTION');
    }
};
