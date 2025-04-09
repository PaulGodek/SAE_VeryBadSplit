<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use PDO;

class UtilisateurRepository
{

    public function recuperer($login) : ?Utilisateur {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            "SELECT DISTINCT 
                        loginProprietaire, nomProprietaire, prenomProprietaire, emailProprietaire, mdpHacheProprietaire, mdpProprietaire
                        FROM app_db
                        WHERE loginProprietaire = :loginProprietaire");
        $pdoStatement->execute(['loginProprietaire' => $login]);
        $data = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        if(!$data) {
            return null;
        }
        return new Utilisateur(
            login: $data["loginProprietaire"],
            nom: $data["nomProprietaire"],
            prenom: $data["prenomProprietaire"],
            email: $data["emailProprietaire"],
            mdpHache: $data["mdpHacheProprietaire"],
            mdp: $data["mdpProprietaire"]
        );
    }

    /**
     * @return Utilisateur[]
     */
    public function recupererParEmail($email) : array {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->query(
            "SELECT DISTINCT 
                        loginProprietaire, nomProprietaire, prenomProprietaire, mdpProprietaire
                        FROM app_db
                        WHERE emailProprietaire = '$email'");
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if(!$data) {
            return [];
        }
        $utilisateurs = [];
        foreach ($data as $utilisateur) {
            $utilisateurs[] = new Utilisateur(
                login: $utilisateur["loginProprietaire"],
                nom: $utilisateur["nomProprietaire"],
                prenom: $utilisateur["prenomProprietaire"],
                mdp: $utilisateur["mdpProprietaire"]
            );
        }
        return $utilisateurs;
    }

    /**
     * @return Utilisateur[]
     */
    public function recupererUtilisateursOrdonnesPrenomNom() : array {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            "SELECT DISTINCT 
                        loginProprietaire, nomProprietaire, prenomProprietaire
                        FROM app_db
                        ORDER BY prenomProprietaire, nomProprietaire");
        $pdoStatement->execute();
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if(!$data) {
            return [];
        }
        $utilisateurs = [];
        foreach ($data as $utilisateur) {
            $utilisateurs[] = new Utilisateur(
                login: $utilisateur["loginProprietaire"],
                nom: $utilisateur["nomProprietaire"],
                prenom: $utilisateur["prenomProprietaire"]
            );
        }
        return $utilisateurs;
    }

    public function mettreAJour(Utilisateur $utilisateur): void
    {
        $map = [
            "loginProprietaire" => $utilisateur->getLogin(),
            "nomProprietaire" => $utilisateur->getNom(),
            "prenomProprietaire" => $utilisateur->getPrenom(),
            "emailProprietaire" => $utilisateur->getEmail(),
            "mdpHacheProprietaire" => $utilisateur->getMdpHache(),
            "mdpProprietaire" => $utilisateur->getMdp()
        ];

        $nomsColonnes = array_keys($map);
        $setArray = array_map(function ($nomcolonne) {
            return "$nomcolonne=:$nomcolonne";
        }, $nomsColonnes);
        $setString = join(', ', $setArray);
        $sql = "UPDATE app_db SET $setString WHERE loginProprietaire=:loginProprietaire";
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);
        $pdoStatement->execute($map);
    }

    public function supprimer(string $login): bool
    {
        $sql = "DELETE FROM app_db WHERE loginProprietaire=:loginProprietaire";
        $pdoStatement = ConnexionBaseDeDonnees::getPDO()->prepare($sql);
        $pdoStatement->execute(['loginProprietaire' => $login]);
        if (!($pdoStatement->rowCount() > 0)) {
            return false;
        }
        return true;
    }
}