<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'UTILISATEUR';

    protected $primaryKey = 'id_user';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'id_user', 'nom', 'prenom', 'email', 'mot_de_passe', 'id_role', 'id_etablissement',
    ];

    protected $hidden = ['mot_de_passe'];

    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe';
    }

    public function getAuthPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function isAdministrator(): bool
    {
        return $this->id_role === 'role-admin';
    }

    public function isService(): bool
    {
        return $this->id_role === 'role-service';
    }

    public function isDoctor(): bool
    {
        return $this->id_role === 'role-doctor';
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
