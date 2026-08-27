<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
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

        $userId = DB::table('UTILISATEUR')
            ->where('email', 'medecin@healthpass.test')
            ->value('id_user');

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

        $patient = [
            'id_patient' => 'patient-sergio-exauce-demo',
            'npi' => 'NPI-BJ-DEMO-0001',
            'nom' => 'SERGIO',
            'prenom' => 'Exaucé',
            'sexe' => 'M',
            'date_naissance' => '1992-05-14',
            'email' => 'sergio.exauce@healthpass.test',
            'telephone' => '+229 97 45 18 62',
            'id_etablissement' => 'etab-demo',
        ];

        if (Schema::hasColumn('PATIENT', 'taille')) {
            $patient['taille'] = 1.74;
            $patient['poids'] = 72.0;
            $patient['groupe_sanguin'] = 'O+';
            $patient['contact_urgence_nom'] = 'Afi Sergio';
            $patient['contact_urgence_lien'] = 'Sœur';
            $patient['contact_urgence_telephone'] = '+229 96 32 74 11';
            $patient['contact_urgence_email'] = 'afi.sergio@healthpass.test';
            $patient['contact_urgence_adresse'] = 'Abomey-Calavi, Bénin';
        }

        DB::table('PATIENT')->updateOrInsert(
            ['id_patient' => $patient['id_patient']],
            $patient
        );

        if (Schema::hasTable('RENDEZ_VOUS')) {
            DB::table('RENDEZ_VOUS')->updateOrInsert(
                ['id_rendez_vous' => 'rdv-sergio-exauce-demo'],
                [
                    'id_patient' => $patient['id_patient'],
                    'id_docteur' => 'doctor-demo',
                    'id_etablissement' => 'etab-demo',
                    'date_rdv' => today(),
                    'heure_rdv' => '09:30',
                    'motif' => 'Consultation de suivi',
                    'statut' => 'Planifié',
                ]
            );
        }
    }
}
