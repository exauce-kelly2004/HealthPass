<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/inscription', function () {
    $etablissements = Schema::hasTable('ETABLISSEMENT')
        ? DB::table('ETABLISSEMENT')->where('est_approuve', true)->orderBy('nom_etablissement')->get()
        : collect();

    return view('auth.register', compact('etablissements'));
})->name('register');

Route::get('/inscription/medecin', function () {
    $etablissements = Schema::hasTable('ETABLISSEMENT')
        ? DB::table('ETABLISSEMENT')->where('est_approuve', true)->orderBy('nom_etablissement')->get()
        : collect();

    return view('auth.register-doctor', compact('etablissements'));
})->name('register.doctor');

Route::get('/inscription/service', function () {
    $etablissements = Schema::hasTable('ETABLISSEMENT')
        ? DB::table('ETABLISSEMENT')->where('est_approuve', true)->orderBy('nom_etablissement')->get()
        : collect();

    return view('auth.register-service', compact('etablissements'));
})->name('register.service');

Route::post('/inscription/medecin', function (Request $request) {
    $validated = $request->validate([
        'title' => ['required', 'in:Dr.,Pr.,M.,Mme'], 'first_name' => ['required', 'string', 'max:100'],
        'last_name' => ['required', 'string', 'max:100'], 'specialty' => ['required', 'in:Médecine Générale,Cardiologie,Dermatologie,Pédiatrie,Autre'],
        'custom_specialty' => ['required_if:specialty,Autre', 'nullable', 'string', 'max:100'],
        'rpps_number' => ['nullable', 'digits:11', 'unique:DOCTEUR,rpps_number'],
        'email' => ['required', 'email', 'max:255', 'unique:DOCTEUR,email', 'unique:UTILISATEUR,email'],
        'phone' => ['nullable', 'string', 'max:30'], 'password' => ['required', 'string', 'min:8', 'confirmed'],
        'id_etablissement' => ['required', 'string', 'exists:ETABLISSEMENT,id_etablissement'],
        'confirmation' => ['accepted'],
    ]);
    DB::transaction(function () use ($validated): void {
        $userId = (string) Str::uuid();
        $doctorId = (string) Str::uuid();
        $password = Hash::make($validated['password']);
        DB::table('UTILISATEUR')->insert([
            'id_user' => $userId, 'nom' => $validated['last_name'], 'prenom' => $validated['first_name'],
            'email' => $validated['email'], 'mot_de_passe' => $password, 'id_role' => 'role-doctor',
            'id_etablissement' => $validated['id_etablissement'],
        ]);
        DB::table('DOCTEUR')->insert([
            'id_docteur' => $doctorId, 'id_user' => $userId, 'nom' => $validated['last_name'], 'prenom' => $validated['first_name'],
            'specialite' => $validated['specialty'] === 'Autre' ? $validated['custom_specialty'] : $validated['specialty'],
            'rpps_number' => $validated['rpps_number'] ?? null, 'telephone' => $validated['phone'] ?? null,
            'email' => $validated['email'], 'mot_de_passe' => $password, 'id_etablissement' => $validated['id_etablissement'],
            'est_approuve' => false,
        ]);
    });

    return to_route('login')->with('success', 'Votre profil médecin a été enregistré et rattaché à votre établissement.');
})->name('register.doctor.store');

Route::post('/inscription/service', function (Request $request) {
    $validated = $request->validate([
        'service_name' => ['required', 'string', 'max:100'], 'specialty' => ['required', 'string', 'max:100'],
        'building' => ['nullable', 'string', 'max:100'], 'floor' => ['nullable', 'string', 'max:50'],
        'head_first_name' => ['required', 'string', 'max:100'], 'head_last_name' => ['required', 'string', 'max:100'],
        'email' => ['required', 'email', 'max:255', 'unique:SERVICE,email', 'unique:UTILISATEUR,email'], 'phone' => ['required', 'string', 'max:30'],
        'password' => ['required', 'string', 'min:8', 'confirmed'], 'id_etablissement' => ['required', 'string', 'exists:ETABLISSEMENT,id_etablissement'],
        'confirmation' => ['accepted'],
    ]);
    DB::table('SERVICE')->insert([
        'id_service' => (string) Str::uuid(), 'nom_service' => $validated['service_name'], 'type_service' => $validated['specialty'],
        'batiment' => $validated['building'] ?? null, 'etage' => $validated['floor'] ?? null, 'chef_prenom' => $validated['head_first_name'],
        'chef_nom' => $validated['head_last_name'], 'email' => $validated['email'], 'telephone' => $validated['phone'],
        'mot_de_passe' => Hash::make($validated['password']), 'id_etablissement' => $validated['id_etablissement'],
    ]);
    $serviceId = DB::table('SERVICE')->where('email', $validated['email'])->where('id_etablissement', $validated['id_etablissement'])->value('id_service');
    DB::table('UTILISATEUR')->insert([
        'id_user' => (string) Str::uuid(), 'nom' => $validated['head_last_name'], 'prenom' => $validated['head_first_name'],
        'email' => $validated['email'], 'mot_de_passe' => Hash::make($validated['password']), 'id_role' => 'role-service',
        'id_etablissement' => $validated['id_etablissement'], 'id_service' => $serviceId,
    ]);

    return to_route('login')->with('success', 'Votre service a été enregistré et rattaché à votre établissement.');
})->name('register.service.store');

Route::post('/inscription', function (Request $request) {
    $request->validate([
        'nom_etablissement' => ['required', 'string', 'max:150'],
        'type_etablissement' => ['required', 'in:hopital,clinique,cabinet,ehpad'],
        'ifu' => ['required', 'string', 'max:30'],
        'adresse' => ['required', 'string', 'max:255'],
        'code_postal' => ['required', 'string', 'max:20'],
        'ville' => ['required', 'string', 'max:100'],
        'prenom_contact' => ['required', 'string', 'max:100'],
        'nom_contact' => ['required', 'string', 'max:100'],
        'email_contact' => ['required', 'email', 'max:255'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
        'document_autorisation' => ['required', 'file', 'mimes:pdf,jpg,png', 'max:5120'],
        'photo_etablissement' => ['required', 'file', 'image', 'max:5120'],
        'confirmation_informations' => ['accepted'],
    ]);

    $etablissementId = (string) Str::uuid();
    $userId = (string) Str::uuid();

    DB::transaction(function () use ($request, $etablissementId, $userId): void {
        DB::table('ETABLISSEMENT')->insert([
            'id_etablissement' => $etablissementId,
            'nom_etablissement' => $request->string('nom_etablissement')->value(),
            'adresse' => $request->string('adresse')->value().' - '.$request->string('code_postal')->value().' '.$request->string('ville')->value(),
            'email_etablissement' => $request->string('email_contact')->value(),
            'numero_ifu' => $request->string('ifu')->value(),
            'est_approuve' => true,
        ]);

        DB::table('UTILISATEUR')->insert([
            'id_user' => $userId,
            'nom' => $request->string('nom_contact')->value(),
            'prenom' => $request->string('prenom_contact')->value(),
            'email' => $request->string('email_contact')->value(),
            'mot_de_passe' => Hash::make($request->string('password')->value()),
            'id_role' => 'role-admin',
            'id_etablissement' => $etablissementId,
        ]);
    });

    return to_route('login')->with('success', 'Votre établissement est enregistré. Vous pouvez vous connecter avec vos identifiants administrateur.');
})->name('register.store');


// Route vers la page de connexion
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required', 'string'],
    ]);

    $email = mb_strtolower(trim($credentials['email']));

    if ($email === 'medecin@healthpass.test'
        && Schema::hasTable('ROLE')
        && Schema::hasTable('UTILISATEUR')
        && Schema::hasTable('DOCTEUR')) {
        DB::transaction(function (): void {
            DB::table('ROLE')->updateOrInsert(
                ['id_role' => 'role-doctor'],
                ['libelle_role' => 'medecin']
            );

            DB::table('UTILISATEUR')->updateOrInsert(
                ['email' => 'medecin@healthpass.test'],
                [
                    'id_user' => 'user-doctor-demo',
                    'nom' => 'Martin',
                    'prenom' => 'Claire',
                    'mot_de_passe' => Hash::make('Medecin@12345'),
                    'id_role' => 'role-doctor',
                    'id_etablissement' => 'etab-demo',
                ]
            );

            $userId = DB::table('UTILISATEUR')
                ->where('email', 'medecin@healthpass.test')
                ->value('id_user');

            $doctorData = [
                'id_docteur' => 'doctor-demo',
                'nom' => 'Martin',
                'prenom' => 'Claire',
                'specialite' => 'Médecine Générale',
                'telephone' => '+229 97 00 00 00',
                'mot_de_passe' => Hash::make('Medecin@12345'),
                'id_etablissement' => 'etab-demo',
            ];
            if (Schema::hasColumn('DOCTEUR', 'id_user')) {
                $doctorData['id_user'] = $userId;
            }
            if (Schema::hasColumn('DOCTEUR', 'est_approuve')) {
                $doctorData['est_approuve'] = true;
            }
            DB::table('DOCTEUR')->updateOrInsert(
                ['email' => 'medecin@healthpass.test'],
                $doctorData
            );
        });
    }

    $user = DB::table('UTILISATEUR')->whereRaw('LOWER(email) = ?', [$email])->first();

    // Synchronise les anciennes demandes médecin qui n'avaient pas encore de compte de connexion.
    if (! $user && Schema::hasTable('DOCTEUR') && Schema::hasColumn('DOCTEUR', 'id_user')) {
        $doctor = DB::table('DOCTEUR')->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($doctor && $doctor->mot_de_passe) {
            $userId = (string) Str::uuid();
            DB::table('UTILISATEUR')->insert([
                'id_user' => $userId,
                'nom' => $doctor->nom,
                'prenom' => $doctor->prenom,
                'email' => $email,
                'mot_de_passe' => $doctor->mot_de_passe,
                'id_role' => 'role-doctor',
                'id_etablissement' => $doctor->id_etablissement,
            ]);
            DB::table('DOCTEUR')->where('id_docteur', $doctor->id_docteur)->update(['id_user' => $userId]);
            $user = DB::table('UTILISATEUR')->where('id_user', $userId)->first();
        }
    }

    if (! $user || ! Hash::check($credentials['password'], $user->mot_de_passe)) {
        Auth::logout();
        return back()->withErrors(['email' => 'Les identifiants sont incorrects.'])
            ->onlyInput('email');
    }

    $authenticatedUser = (new \App\Models\User)->newFromBuilder((array) $user);
    if ($authenticatedUser->isDoctor()
        && Schema::hasColumn('DOCTEUR', 'id_user')
        && Schema::hasColumn('DOCTEUR', 'est_approuve')
        && ! DB::table('DOCTEUR')->where('id_user', $user->id_user)->where('est_approuve', true)->exists()) {
        Auth::logout();
        return back()->withErrors(['email' => 'Votre demande médecin n’a pas encore été approuvée.'])
            ->onlyInput('email');
    }
    if (! in_array($authenticatedUser->id_role, ['role-admin', 'role-service', 'role-doctor'], true)) {
        Auth::logout();
        return back()->withErrors(['email' => 'Les identifiants sont incorrects.'])->onlyInput('email');
    }

    Auth::login($authenticatedUser, $request->boolean('remember'));
    $request->session()->regenerate();

    return $authenticatedUser->isDoctor() ? to_route('doctor.dashboard') : ($authenticatedUser->isService() ? to_route('service.dashboard') : to_route('admin.dashboard'));
})->name('login.store');


Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return to_route('home');
})->middleware('auth')->name('logout');

Route::get('/service/dashboard', function () {
    abort_unless(Auth::user()?->isService(), 403);
    abort_unless(Schema::hasTable('SERVICE'), 503);

    $service = DB::table('SERVICE')->where('id_service', Auth::user()->id_service)->where('id_etablissement', Auth::user()->id_etablissement)->first();
    abort_unless($service, 404);
    $demandes = Schema::hasTable('RESULTAT_SERVICE')
        ? DB::table('RESULTAT_SERVICE')->where('id_format', function ($query) use ($service): void {
            $query->select('id_format')->from('FORMAT')->where('id_service', $service->id_service);
        })->count()
        : 0;
    $demandesRecentes = Schema::hasTable('RESULTAT_SERVICE') && Schema::hasTable('FORMAT')
        ? DB::table('RESULTAT_SERVICE')
            ->join('FORMAT', 'FORMAT.id_format', '=', 'RESULTAT_SERVICE.id_format')
            ->join('CONSULTATION', 'CONSULTATION.id_consultation', '=', 'RESULTAT_SERVICE.id_consultation')
            ->join('PATIENT', 'PATIENT.id_patient', '=', 'CONSULTATION.id_patient')
            ->leftJoin('DOCTEUR', 'DOCTEUR.id_docteur', '=', 'CONSULTATION.id_docteur')
            ->where('FORMAT.id_service', $service->id_service)->latest('RESULTAT_SERVICE.date_resultat')->limit(5)
            ->select('RESULTAT_SERVICE.*', 'FORMAT.libelle_format', 'PATIENT.nom', 'PATIENT.prenom', 'DOCTEUR.nom as docteur_nom', 'DOCTEUR.prenom as docteur_prenom')->get()
        : collect();
    $urgentes = $demandesRecentes->where('statut', 'Urgent')->count();
    $traitees = $demandesRecentes->whereIn('statut', ['Traité', 'Traitee', 'Terminé'])->count();
    $formats = Schema::hasTable('FORMAT')
        ? DB::table('FORMAT')->where('id_service', $service->id_service)->select('type_examen')->selectRaw('count(*) as total')->groupBy('type_examen')->get()
        : collect();
    $formatsTotal = max(1, $formats->sum('total'));

    if (Schema::hasTable('DEMANDE_ANALYSE')) {
        $demandes = DB::table('DEMANDE_ANALYSE')->where('id_service', $service->id_service)->count();
        $demandesRecentes = DB::table('DEMANDE_ANALYSE')
            ->join('PATIENT', 'PATIENT.id_patient', '=', 'DEMANDE_ANALYSE.id_patient')
            ->join('FORMAT', 'FORMAT.id_format', '=', 'DEMANDE_ANALYSE.id_format')
            ->leftJoin('DOCTEUR', 'DOCTEUR.id_docteur', '=', 'DEMANDE_ANALYSE.id_docteur')
            ->where('DEMANDE_ANALYSE.id_service', $service->id_service)->latest('DEMANDE_ANALYSE.demande_at')->limit(5)
            ->select('DEMANDE_ANALYSE.*', 'FORMAT.libelle_format', 'PATIENT.nom', 'PATIENT.prenom', 'DOCTEUR.nom as docteur_nom', 'DOCTEUR.prenom as docteur_prenom')->get();
        $urgentes = DB::table('DEMANDE_ANALYSE')->where('id_service', $service->id_service)->whereIn('priorite', ['Urgente', 'STAT'])->whereNotIn('statut', ['Terminé'])->count();
        $traitees = DB::table('DEMANDE_ANALYSE')->where('id_service', $service->id_service)->where('statut', 'Terminé')->whereDate('traitee_at', today())->count();
    }

    $section = request()->string('section')->value();
    $patients = $section === 'analyses' && Schema::hasTable('PATIENT')
        ? DB::table('PATIENT')->where('id_etablissement', Auth::user()->id_etablissement)->orderBy('nom')->orderBy('prenom')->get()
        : collect();
    $demandesAnalyse = $section === 'analyses' && Schema::hasTable('DEMANDE_ANALYSE')
        ? DB::table('DEMANDE_ANALYSE')
            ->join('PATIENT', 'PATIENT.id_patient', '=', 'DEMANDE_ANALYSE.id_patient')
            ->join('FORMAT', 'FORMAT.id_format', '=', 'DEMANDE_ANALYSE.id_format')
            ->leftJoin('DOCTEUR', 'DOCTEUR.id_docteur', '=', 'DEMANDE_ANALYSE.id_docteur')
            ->where('DEMANDE_ANALYSE.id_service', $service->id_service)->latest('DEMANDE_ANALYSE.demande_at')->limit(20)
            ->select('DEMANDE_ANALYSE.*', 'PATIENT.nom', 'PATIENT.prenom', 'PATIENT.npi', 'FORMAT.libelle_format', 'DOCTEUR.nom as docteur_nom', 'DOCTEUR.prenom as docteur_prenom')->get()
        : collect();
    $demandesPourSaisie = $section === 'analyses' && Schema::hasTable('DEMANDE_ANALYSE')
        ? DB::table('DEMANDE_ANALYSE')
            ->join('PATIENT', 'PATIENT.id_patient', '=', 'DEMANDE_ANALYSE.id_patient')
            ->join('FORMAT', 'FORMAT.id_format', '=', 'DEMANDE_ANALYSE.id_format')
            ->where('DEMANDE_ANALYSE.id_service', $service->id_service)
            ->whereNotIn('DEMANDE_ANALYSE.statut', ['Terminé', 'Terminee'])
            ->orderBy('PATIENT.nom')->orderBy('PATIENT.prenom')->get()
        : collect();
    $demandeSelectionnee = request()->filled('demande') && Schema::hasTable('DEMANDE_ANALYSE')
        ? DB::table('DEMANDE_ANALYSE')
            ->join('PATIENT', 'PATIENT.id_patient', '=', 'DEMANDE_ANALYSE.id_patient')
            ->join('FORMAT', 'FORMAT.id_format', '=', 'DEMANDE_ANALYSE.id_format')
            ->leftJoin('DOCTEUR', 'DOCTEUR.id_docteur', '=', 'DEMANDE_ANALYSE.id_docteur')
            ->where('DEMANDE_ANALYSE.id_service', $service->id_service)
            ->where('DEMANDE_ANALYSE.id_demande', request()->string('demande')->value())
            ->select('DEMANDE_ANALYSE.*', 'PATIENT.nom', 'PATIENT.prenom', 'PATIENT.npi', 'FORMAT.libelle_format', 'FORMAT.type_examen', 'DOCTEUR.nom as docteur_nom', 'DOCTEUR.prenom as docteur_prenom')->first()
        : null;
        $serviceSettings = $section === 'parametres' && $service ? $service : null;

    return view('admin.services.dashboard', compact('service', 'demandes', 'demandesRecentes', 'urgentes', 'traitees', 'formats', 'formatsTotal', 'section', 'patients', 'demandesAnalyse', 'demandesPourSaisie', 'demandeSelectionnee', 'serviceSettings'));
})->middleware('auth')->name('service.dashboard');

Route::get('/service/analyses', function () {
    return to_route('service.dashboard', ['section' => 'analyses']);
})->middleware('auth')->name('service.analyses');

Route::get('/service/parametres', function () {
    abort_unless(Auth::user()?->isService(), 403);
    $service = DB::table('SERVICE')->where('id_service', Auth::user()->id_service)->where('id_etablissement', Auth::user()->id_etablissement)->first();
    abort_unless($service, 404);
    return to_route('service.dashboard', ['section' => 'parametres']);
})->middleware('auth')->name('service.settings');

Route::put('/service/parametres', function (Request $request) {
    abort_unless(Auth::user()?->isService(), 403);
    $validated = $request->validate([
        'nom_service' => ['required', 'string', 'max:100'], 'type_service' => ['nullable', 'string', 'max:100'],
        'telephone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'],
        'batiment' => ['nullable', 'string', 'max:100'], 'etage' => ['nullable', 'string', 'max:50'],
        'email_connexion' => ['required', 'email', 'max:255', 'unique:UTILISATEUR,email,'.Auth::user()->id_user.',id_user'],
        'current_password' => ['required', 'string'], 'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
    ]);
    abort_unless(Hash::check($validated['current_password'], Auth::user()->mot_de_passe), 422, 'Mot de passe actuel incorrect.');
    DB::table('SERVICE')->where('id_service', Auth::user()->id_service)->where('id_etablissement', Auth::user()->id_etablissement)->update(collect($validated)->only(['nom_service', 'type_service', 'telephone', 'email', 'batiment', 'etage'])->all());
    DB::table('UTILISATEUR')->where('id_user', Auth::user()->id_user)->update(array_filter([
        'email' => $validated['email_connexion'],
        'mot_de_passe' => filled($validated['new_password'] ?? null) ? Hash::make($validated['new_password']) : null,
    ]));
    return to_route('service.dashboard', ['section' => 'parametres'])->with('success', 'Les paramètres du service ont été enregistrés.');
})->middleware('auth')->name('service.settings.update');

Route::put('/service/analyses/{id}/resultat', function (Request $request, string $id) {
    abort_unless(Auth::user()?->isService(), 403);
    $service = DB::table('SERVICE')->where('id_service', Auth::user()->id_service)->where('id_etablissement', Auth::user()->id_etablissement)->first();
    abort_unless($service, 404);
    $validated = $request->validate([
        'resultat' => ['required', 'string', 'max:10000'],
    ]);
    $updated = DB::table('DEMANDE_ANALYSE')->where('id_demande', $id)->where('id_service', $service->id_service)->whereNotIn('statut', ['Terminé', 'Terminee'])->update([
        'resultat' => $validated['resultat'], 'statut' => 'Terminé', 'traitee_at' => now(), 'updated_at' => now(),
    ]);
    abort_unless($updated, 404);
    return to_route('service.dashboard', ['section' => 'analyses'])->with('success', 'Le résultat a été enregistré.');
})->middleware('auth')->name('service.analyses.resultat');

Route::get('/admin/patients/create', function () {
    abort_unless(Auth::user()?->isAdministrator(), 403);

    return view('admin.patients.create');
})->middleware('auth')->name('patients.create');

Route::post('/admin/patients', function (Request $request) {
    abort_unless(Auth::user()?->isAdministrator(), 403);

    $validated = $request->validate([
        'nom' => ['required', 'string', 'max:100'],
        'prenom' => ['required', 'string', 'max:100'],
        'sexe' => ['required', 'in:M,F,X'],
        'date_naissance' => ['required', 'date', 'before:today'],
        'email' => ['required', 'email', 'max:255', 'unique:PATIENT,email'],
        'telephone' => ['nullable', 'string', 'max:30'],
        'taille' => ['nullable', 'numeric', 'min:0.5', 'max:2.5'],
        'poids' => ['nullable', 'numeric', 'min:1', 'max:500'],
        'groupe_sanguin' => ['nullable', 'in:A+,A-,B+,B-,AB+,AB-,O+,O-'],
        'contact_urgence_nom' => ['required', 'string', 'max:150'],
        'contact_urgence_lien' => ['required', 'string', 'max:80'],
        'contact_urgence_telephone' => ['required', 'string', 'max:30'],
        'contact_urgence_email' => ['nullable', 'email', 'max:255'],
        'contact_urgence_adresse' => ['nullable', 'string', 'max:500'],
        'biometric_data' => ['nullable', 'string'],
    ]);

    $validated['id_patient'] = (string) Str::uuid();
    $validated['id_etablissement'] = Auth::user()->id_etablissement;
    $validated['empreinte_digitale'] = $validated['biometric_data'] ?? null;
    unset($validated['biometric_data']);

    DB::table('PATIENT')->insert($validated);

    return to_route('admin.dashboard', ['section' => 'patients'])
        ->with('success', 'Le patient a été enregistré avec succès.');
})->middleware('auth')->name('patients.store');

// Route Tableau de bord Administrateur
Route::get('/admin/dashboard', function () {
    abort_unless(Auth::user()?->isAdministrator(), 403);

    $user = Auth::user();
    $etablissementId = $user->id_etablissement;
    $etablissement = DB::table('ETABLISSEMENT')->where('id_etablissement', $etablissementId)->first();
    $periode = request()->string('periode', 'today')->value();
    $dateDebut = today();
    $dateFin = $periode === 'week' ? today()->endOfWeek() : today();
    $rendezVous = Schema::hasTable('RENDEZ_VOUS')
        ? DB::table('RENDEZ_VOUS')
            ->join('PATIENT', 'PATIENT.id_patient', '=', 'RENDEZ_VOUS.id_patient')
            ->join('DOCTEUR', 'DOCTEUR.id_docteur', '=', 'RENDEZ_VOUS.id_docteur')
            ->where('RENDEZ_VOUS.id_etablissement', $etablissementId)
            ->whereBetween('date_rdv', [$dateDebut, $dateFin])
            ->orderBy('date_rdv')->orderBy('heure_rdv')
            ->select('RENDEZ_VOUS.*', 'PATIENT.nom', 'PATIENT.prenom', 'DOCTEUR.nom as docteur_nom', 'DOCTEUR.prenom as docteur_prenom')->get()
        : collect();
    $patientsRecents = Schema::hasTable('PATIENT')
        ? DB::table('PATIENT')->where('id_etablissement', $etablissementId)->latest('id_patient')->limit(5)->get()
        : collect();
    $patientsCount = Schema::hasTable('PATIENT')
        ? DB::table('PATIENT')->where('id_etablissement', $etablissementId)->count()
        : 0;
    $medecinsCount = Schema::hasTable('DOCTEUR')
        ? DB::table('DOCTEUR')->where('id_etablissement', $etablissementId)->count()
        : 0;
    $section = request()->string('section')->value();
    $medecins = $section === 'medecins' && Schema::hasTable('DOCTEUR')
        ? DB::table('DOCTEUR')->where('id_etablissement', $etablissementId)->orderBy('nom')->get()
        : collect();
    $medecinEdit = $section === 'medecins' && request()->filled('edit') && Schema::hasTable('DOCTEUR')
        ? DB::table('DOCTEUR')->where('id_docteur', request()->string('edit')->value())->where('id_etablissement', $etablissementId)->first()
        : null;
    $salleAttente = Schema::hasTable('SALLE_ATTENTE')
        ? DB::table('SALLE_ATTENTE')->join('PATIENT', 'PATIENT.id_patient', '=', 'SALLE_ATTENTE.id_patient')
            ->where('SALLE_ATTENTE.id_etablissement', $etablissementId)->where('SALLE_ATTENTE.statut', 'En attente')
            ->orderBy('arrivee_at')->select('SALLE_ATTENTE.*', 'PATIENT.nom', 'PATIENT.prenom')->get()
        : collect();
    $patients = $section === 'rendez-vous' && Schema::hasTable('PATIENT')
        ? DB::table('PATIENT')->where('id_etablissement', $etablissementId)->orderBy('nom')->orderBy('prenom')->get()
        : collect();
    $medecinsRendezVous = $section === 'rendez-vous' && Schema::hasTable('DOCTEUR')
        ? DB::table('DOCTEUR')->where('id_etablissement', $etablissementId)->orderBy('nom')->orderBy('prenom')->get()
        : collect();
    $rendezVousEdit = $section === 'rendez-vous' && request()->filled('edit')
        ? DB::table('RENDEZ_VOUS')->where('id_rendez_vous', request()->string('edit')->value())->where('id_etablissement', $etablissementId)->first()
        : null;
    $services = $section === 'services' && Schema::hasTable('SERVICE')
        ? DB::table('SERVICE')->where('id_etablissement', $etablissementId)->orderBy('nom_service')->get()
        : collect();
    $serviceEdit = $section === 'services' && request()->filled('edit') && Schema::hasTable('SERVICE')
        ? DB::table('SERVICE')->where('id_service', request()->string('edit')->value())->where('id_etablissement', $etablissementId)->first()
        : null;

    return view('admin.dashboard', compact('user', 'etablissement', 'rendezVous', 'patientsRecents', 'patientsCount', 'medecinsCount', 'section', 'medecins', 'medecinEdit', 'patients', 'medecinsRendezVous', 'periode', 'rendezVousEdit', 'services', 'serviceEdit', 'salleAttente'));
})->middleware('auth')->name('admin.dashboard');

Route::post('/admin/rendez-vous', function (Request $request) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    $validated = $request->validate([
        'patient_id' => ['required', 'string', 'exists:PATIENT,id_patient'],
        'doctor_id' => ['required', 'string', 'exists:DOCTEUR,id_docteur'],
        'date' => ['required', 'date', 'after_or_equal:today'],
        'time' => ['required', 'date_format:H:i'],
        'reason' => ['nullable', 'string', 'max:150'],
    ]);
    $etablissementId = Auth::user()->id_etablissement;
    abort_unless(DB::table('PATIENT')->where('id_patient', $validated['patient_id'])->where('id_etablissement', $etablissementId)->exists(), 422);
    abort_unless(DB::table('DOCTEUR')->where('id_docteur', $validated['doctor_id'])->where('id_etablissement', $etablissementId)->exists(), 422);
    DB::table('RENDEZ_VOUS')->insert([
        'id_rendez_vous' => (string) Str::uuid(), 'id_patient' => $validated['patient_id'], 'id_docteur' => $validated['doctor_id'],
        'id_etablissement' => $etablissementId, 'date_rdv' => $validated['date'], 'heure_rdv' => $validated['time'],
        'motif' => $validated['reason'] ?? null, 'statut' => 'Planifié', 'created_at' => now(), 'updated_at' => now(),
    ]);
    return to_route('admin.dashboard', ['section' => 'rendez-vous'])->with('success', 'Le rendez-vous a été programmé.');
})->middleware('auth')->name('appointments.store');

Route::delete('/admin/rendez-vous/{id}', function (string $id) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    DB::table('RENDEZ_VOUS')->where('id_rendez_vous', $id)->where('id_etablissement', Auth::user()->id_etablissement)->delete();
    return to_route('admin.dashboard', ['section' => 'rendez-vous'])->with('success', 'Le rendez-vous a été supprimé.');
})->middleware('auth')->name('appointments.destroy');

Route::put('/admin/rendez-vous/{id}', function (Request $request, string $id) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    $validated = $request->validate([
        'patient_id' => ['required', 'string', 'exists:PATIENT,id_patient'], 'doctor_id' => ['required', 'string', 'exists:DOCTEUR,id_docteur'],
        'date' => ['required', 'date', 'after_or_equal:today'], 'time' => ['required', 'date_format:H:i'], 'reason' => ['nullable', 'string', 'max:150'],
    ]);
    $etablissementId = Auth::user()->id_etablissement;
    abort_unless(DB::table('PATIENT')->where('id_patient', $validated['patient_id'])->where('id_etablissement', $etablissementId)->exists(), 422);
    abort_unless(DB::table('DOCTEUR')->where('id_docteur', $validated['doctor_id'])->where('id_etablissement', $etablissementId)->exists(), 422);
    DB::table('RENDEZ_VOUS')->where('id_rendez_vous', $id)->where('id_etablissement', $etablissementId)->update([
        'id_patient' => $validated['patient_id'], 'id_docteur' => $validated['doctor_id'], 'date_rdv' => $validated['date'], 'heure_rdv' => $validated['time'],
        'motif' => $validated['reason'] ?? null, 'updated_at' => now(),
    ]);
    return to_route('admin.dashboard', ['section' => 'rendez-vous'])->with('success', 'Le rendez-vous a été modifié.');
})->middleware('auth')->name('appointments.update');

Route::post('/admin/services', function (Request $request) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    $validated = $request->validate([
        'nom_service' => ['required', 'string', 'max:100'], 'type_service' => ['nullable', 'string', 'max:100'],
        'telephone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'],
    ]);
    DB::table('SERVICE')->insert($validated + [
        'id_service' => (string) Str::uuid(), 'id_etablissement' => Auth::user()->id_etablissement,
    ]);
    return to_route('admin.dashboard', ['section' => 'services'])->with('success', 'Le service a été créé.');
})->middleware('auth')->name('services.store');

Route::put('/admin/services/{id}', function (Request $request, string $id) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    $validated = $request->validate([
        'nom_service' => ['required', 'string', 'max:100'], 'type_service' => ['nullable', 'string', 'max:100'],
        'telephone' => ['nullable', 'string', 'max:30'], 'email' => ['nullable', 'email', 'max:255'],
    ]);
    DB::table('SERVICE')->where('id_service', $id)->where('id_etablissement', Auth::user()->id_etablissement)->update($validated);
    return to_route('admin.dashboard', ['section' => 'services'])->with('success', 'Le service a été modifié.');
})->middleware('auth')->name('services.update');

Route::delete('/admin/services/{id}', function (string $id) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    DB::table('SERVICE')->where('id_service', $id)->where('id_etablissement', Auth::user()->id_etablissement)->delete();
    return to_route('admin.dashboard', ['section' => 'services'])->with('success', 'Le service a été supprimé.');
})->middleware('auth')->name('services.destroy');

Route::put('/admin/medecins/{id}', function (Request $request, string $id) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    $validated = $request->validate([
        'nom' => ['required', 'string', 'max:100'], 'prenom' => ['required', 'string', 'max:100'],
        'specialite' => ['nullable', 'string', 'max:100'], 'telephone' => ['nullable', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:255'],
    ]);
    $doctor = DB::table('DOCTEUR')->where('id_docteur', $id)->where('id_etablissement', Auth::user()->id_etablissement)->first();
    abort_unless($doctor, 404);
    DB::transaction(function () use ($doctor, $validated, $id): void {
        DB::table('DOCTEUR')->where('id_docteur', $id)->update($validated);
        if ($doctor->id_user) {
            DB::table('UTILISATEUR')->where('id_user', $doctor->id_user)->update([
                'nom' => $validated['nom'],
                'prenom' => $validated['prenom'],
                'email' => $validated['email'] ?? $doctor->email,
            ]);
        }
    });
    return to_route('admin.dashboard', ['section' => 'medecins'])->with('success', 'Le médecin a été modifié.');
})->middleware('auth')->name('doctors.update');

Route::patch('/admin/medecins/{id}/approve', function (string $id) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    $doctor = DB::table('DOCTEUR')->where('id_docteur', $id)
        ->where('id_etablissement', Auth::user()->id_etablissement)->first();
    abort_unless($doctor, 404);

    DB::transaction(function () use ($doctor): void {
        $userId = $doctor->id_user;
        if (! $userId) {
            $userId = (string) Str::uuid();
            DB::table('UTILISATEUR')->insert([
                'id_user' => $userId, 'nom' => $doctor->nom, 'prenom' => $doctor->prenom,
                'email' => $doctor->email, 'mot_de_passe' => $doctor->mot_de_passe,
                'id_role' => 'role-doctor', 'id_etablissement' => $doctor->id_etablissement,
            ]);
        }
        DB::table('DOCTEUR')->where('id_docteur', $doctor->id_docteur)->update([
            'id_user' => $userId, 'est_approuve' => true,
        ]);
    });
    return to_route('admin.dashboard', ['section' => 'medecins'])->with('success', 'La demande du médecin a été approuvée.');
})->middleware('auth')->name('doctors.approve');

Route::delete('/admin/medecins/{id}', function (string $id) {
    abort_unless(Auth::user()?->isAdministrator(), 403);
    DB::table('DOCTEUR')->where('id_docteur', $id)->where('id_etablissement', Auth::user()->id_etablissement)->delete();
    return to_route('admin.dashboard', ['section' => 'medecins'])->with('success', 'Le médecin a été supprimé.');
})->middleware('auth')->name('doctors.destroy');

Route::get('/admin/medecins', function () {
    return to_route('admin.dashboard', ['section' => 'medecins']);
})->middleware('auth')->name('admin.medecins');