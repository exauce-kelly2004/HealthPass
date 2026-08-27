<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ROLE')->insertOrIgnore([
            'id_role' => 'role-doctor',
            'libelle_role' => 'medecin',
        ]);

        Schema::table('DOCTEUR', function (Blueprint $table): void {
            $table->string('id_user', 42)->nullable()->unique()->after('id_docteur');
            $table->boolean('est_approuve')->default(false)->after('id_user');
            $table->foreign('id_user')->references('id_user')->on('UTILISATEUR')->nullOnDelete();
        });

        $doctorId = 'doctor-demo';
        $userId = 'user-doctor-demo';

        DB::table('UTILISATEUR')->updateOrInsert(
            ['email' => 'medecin@healthpass.test'],
            [
                'id_user' => $userId,
                'nom' => 'Exaucé',
                'prenom' => 'Sergio',
                'mot_de_passe' => Hash::make('Medecin@12345'),
                'id_role' => 'role-doctor',
                'id_etablissement' => 'etab-demo',
            ]
        );

        $userId = DB::table('UTILISATEUR')->where('email', 'medecin@healthpass.test')->value('id_user');
        DB::table('DOCTEUR')->updateOrInsert(
            ['email' => 'medecin@healthpass.test'],
            [
            'id_docteur' => $doctorId,
            'id_user' => $userId,
            'nom' => 'Exaucé',
            'prenom' => 'Sergio',
            'specialite' => 'Médecine Générale',
            'telephone' => '+229 97 00 00 00',
            'email' => 'medecin@healthpass.test',
            'mot_de_passe' => Hash::make('Medecin@12345'),
            'id_etablissement' => 'etab-demo',
            'est_approuve' => true,
            ]
        );
    }

    public function down(): void
    {
        DB::table('DOCTEUR')->where('id_docteur', 'doctor-demo')->delete();
        DB::table('UTILISATEUR')->where('id_user', 'user-doctor-demo')->delete();

        Schema::table('DOCTEUR', function (Blueprint $table): void {
            $table->dropForeign(['id_user']);
            $table->dropUnique(['id_user']);
            $table->dropColumn(['id_user', 'est_approuve']);
        });

        DB::table('ROLE')->where('id_role', 'role-doctor')->delete();
    }
};
