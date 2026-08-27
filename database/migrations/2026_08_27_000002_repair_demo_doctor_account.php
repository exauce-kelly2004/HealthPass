<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('ROLE')->insertOrIgnore([
            'id_role' => 'role-doctor',
            'libelle_role' => 'medecin',
        ]);

        DB::table('UTILISATEUR')->updateOrInsert(
            ['email' => 'medecin@healthpass.test'],
            [
                'id_user' => 'user-doctor-demo',
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
                'id_docteur' => 'doctor-demo',
                'id_user' => $userId,
                'nom' => 'Exaucé',
                'prenom' => 'Sergio',
                'specialite' => 'Médecine Générale',
                'telephone' => '+229 97 00 00 00',
                'mot_de_passe' => Hash::make('Medecin@12345'),
                'id_etablissement' => 'etab-demo',
                'est_approuve' => true,
            ]
        );
    }

    public function down(): void
    {
        DB::table('DOCTEUR')->where('id_docteur', 'doctor-demo')->delete();
        DB::table('UTILISATEUR')->where('email', 'medecin@healthpass.test')->delete();
    }
};
