<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('CONSULTATION')) {
            Schema::create('CONSULTATION', function (Blueprint $table): void {
                $table->string('id_consultation', 42)->primary();
                $table->date('date_consultation');
                $table->time('heure')->nullable();
                $table->text('motif')->nullable();
                $table->text('symptomes')->nullable();
                $table->text('diagnostic')->nullable();
                $table->text('traitement')->nullable();
                $table->text('observation_medicale')->nullable();
                $table->string('id_patient', 42);
                $table->string('id_docteur', 42);
                $table->string('id_etablissement', 42);
                $table->string('id_rendez_vous', 42)->nullable();
                $table->timestamps();

                $table->foreign('id_patient')->references('id_patient')->on('PATIENT')->cascadeOnDelete();
                $table->foreign('id_docteur')->references('id_docteur')->on('DOCTEUR')->cascadeOnDelete();
                $table->foreign('id_etablissement')->references('id_etablissement')->on('ETABLISSEMENT')->cascadeOnDelete();
                $table->foreign('id_rendez_vous')->references('id_rendez_vous')->on('RENDEZ_VOUS')->nullOnDelete();
                $table->index(['id_docteur', 'date_consultation']);
                $table->index(['id_etablissement', 'date_consultation']);
            });
        }

        if (! Schema::hasTable('ANALYSE')) {
            Schema::create('ANALYSE', function (Blueprint $table): void {
                $table->string('id_analyse', 42)->primary();
                $table->date('date_analyse');
                $table->string('type_analyse', 150);
                $table->text('prescription')->nullable();
                $table->text('observation')->nullable();
                $table->string('statut', 42)->default('Prescrite');
                $table->string('priorite', 42)->default('Normale');
                $table->string('id_patient', 42);
                $table->string('id_docteur', 42);
                $table->string('id_service', 42);
                $table->string('id_etablissement', 42);
                $table->string('id_rendez_vous', 42)->nullable();
                $table->timestamps();

                $table->foreign('id_patient')->references('id_patient')->on('PATIENT')->cascadeOnDelete();
                $table->foreign('id_docteur')->references('id_docteur')->on('DOCTEUR')->cascadeOnDelete();
                $table->foreign('id_service')->references('id_service')->on('SERVICE')->cascadeOnDelete();
                $table->foreign('id_etablissement')->references('id_etablissement')->on('ETABLISSEMENT')->cascadeOnDelete();
                $table->foreign('id_rendez_vous')->references('id_rendez_vous')->on('RENDEZ_VOUS')->nullOnDelete();
                $table->index(['id_docteur', 'date_analyse']);
                $table->index(['id_etablissement', 'date_analyse']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ANALYSE');
        Schema::dropIfExists('CONSULTATION');
    }
};
