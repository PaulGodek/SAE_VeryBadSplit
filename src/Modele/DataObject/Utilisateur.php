<?php

namespace App\VeryBadSplit\Modele\DataObject;

class Utilisateur
{
    public function __construct(
        private string  $login,
        private ?string $nom = null,
        private ?string $prenom = null,
        private ?string $email = null,
        private ?string $mdpHache = null,
        private ?string $mdp = null
    )
    {
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): void
    {
        $this->nom = $nom;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): void
    {
        $this->prenom = $prenom;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getMdpHache(): ?string
    {
        return $this->mdpHache;
    }

    public function setMdpHache(?string $mdpHache): void
    {
        $this->mdpHache = $mdpHache;
    }

    public function getMdp(): ?string
    {
        return $this->mdp;
    }

    public function setMdp(?string $mdp): void
    {
        $this->mdp = $mdp;
    }

    public static function construireUtilisateursDepuisListe(?array $liste): array
    {
        $users = [];
        foreach ($liste as $utilisateur) {
            $users[] = new Utilisateur(
                login: $utilisateur["login"],
                nom: $utilisateur["nom"],
                prenom: $utilisateur["prenom"],
            );
        }
        return $users;
    }

    public static function formatJsonListeUtilisateurs($utilisateurs): string
    {
        $utilisateursToJson = [];
        foreach ($utilisateurs as $utilisateur) {
            $utilisateursToJson[] = [
                "login" => $utilisateur->getLogin(),
                "nom" => $utilisateur->getNom(),
                "prenom" => $utilisateur->getPrenom(),
            ];
        };
        return json_encode($utilisateursToJson);
    }
}