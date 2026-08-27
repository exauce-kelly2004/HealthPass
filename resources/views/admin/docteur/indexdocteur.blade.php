<div class="space-y-8">
    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h2 class="font-titre-ecran text-titre-ecran text-on-surface">Médecins</h2>
            <p class="font-corps-standard text-corps-standard text-on-surface-variant">Tous les médecins rattachés à votre établissement.</p>
        </div>
        <span class="flex items-center gap-2 rounded-full border border-bordure-douce bg-surface px-4 py-2 font-label-fort text-label-fort text-primary"><span class="material-symbols-outlined">stethoscope</span>{{ $medecins->count() }} médecin(s)</span>
    </div>

    @if ($medecinEdit)
        <form action="{{ route('doctors.update', $medecinEdit->id_docteur) }}" method="POST" class="dashboard-card rounded-xl border border-bordure-douce bg-surface p-6">
            @csrf @method('PUT')
            <h3 class="mb-4 font-sous-titre text-sous-titre text-on-surface">Modifier le médecin</h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <input name="prenom" required maxlength="100" value="{{ old('prenom', $medecinEdit->prenom) }}" placeholder="Prénom" class="rounded-lg border border-outline-variant px-3 py-3">
                <input name="nom" required maxlength="100" value="{{ old('nom', $medecinEdit->nom) }}" placeholder="Nom" class="rounded-lg border border-outline-variant px-3 py-3">
                <input name="specialite" maxlength="100" value="{{ old('specialite', $medecinEdit->specialite) }}" placeholder="Spécialité" class="rounded-lg border border-outline-variant px-3 py-3">
                <input name="telephone" maxlength="30" value="{{ old('telephone', $medecinEdit->telephone) }}" placeholder="Téléphone" class="rounded-lg border border-outline-variant px-3 py-3">
                <input name="email" type="email" maxlength="255" value="{{ old('email', $medecinEdit->email) }}" placeholder="Email" class="rounded-lg border border-outline-variant px-3 py-3">
            </div>
            <div class="mt-4 flex gap-3"><button class="rounded-lg bg-primary px-4 py-2 font-label-fort text-on-primary" type="submit">Enregistrer</button><a href="{{ route('admin.dashboard', ['section' => 'medecins']) }}" class="rounded-lg border border-outline-variant px-4 py-2 font-label-fort text-on-surface">Annuler</a></div>
        </form>
    @endif

    <div class="grid grid-cols-1 gap-6">
        <section class="dashboard-card overflow-hidden rounded-xl border border-bordure-douce bg-surface">
            <div class="flex items-center justify-between border-b border-bordure-douce p-4"><h3 class="font-sous-titre text-sous-titre text-on-surface">Liste des médecins</h3><span class="font-label-fort text-label-fort text-primary">{{ $medecins->count() }} enregistré(s)</span></div>
            <div class="divide-y divide-bordure-douce">
                @forelse ($medecins as $medecin)
                    <div class="flex flex-col gap-4 p-4 transition-colors hover:bg-fond-page sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-3"><div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-surface-container-high font-label-fort text-on-surface">{{ strtoupper(substr($medecin->prenom ?: 'D', 0, 1).substr($medecin->nom ?: 'M', 0, 1)) }}</div><div class="min-w-0"><p class="font-label-fort text-label-fort text-on-surface">Dr {{ $medecin->prenom }} {{ $medecin->nom }}</p><p class="font-corps-dense text-corps-dense text-on-surface-variant">{{ $medecin->specialite ?: 'Spécialité non renseignée' }} · {{ $medecin->email ?: 'Email non renseigné' }}</p></div></div>
                        <div class="flex shrink-0 items-center gap-2">
                            @if ($medecin->est_approuve)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700">Approuvé</span>
                            @else
                                <form action="{{ route('doctors.approve', $medecin->id_docteur) }}" method="POST">@csrf @method('PATCH')<button type="submit" class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-on-primary">Approuver</button></form>
                            @endif
                            <a href="{{ route('admin.dashboard', ['section' => 'medecins', 'edit' => $medecin->id_docteur]) }}" aria-label="Modifier ce médecin" title="Modifier" class="rounded-lg p-2 text-secondary hover:bg-secondary-container/20"><span class="material-symbols-outlined">edit</span></a><form action="{{ route('doctors.destroy', $medecin->id_docteur) }}" method="POST">@csrf @method('DELETE')<button type="submit" aria-label="Supprimer ce médecin" title="Supprimer" class="rounded-lg p-2 text-error hover:bg-error-container/40"><span class="material-symbols-outlined">delete</span></button></form>
                        </div>
                    </div>
                @empty
                    <p class="p-8 text-center font-corps-dense text-corps-dense text-on-surface-variant">Aucun médecin enregistré.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
