<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\ConnexionBaseDeDonneesInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use DateTime;
use PDO;

class EvenementRepository implements EvenementRepositoryInterface
{

    public function __construct(private ConnexionBaseDeDonneesInterface $connexionBaseDeDonnees){}

    private function recupererPar($critere, $valeur) : ?Evenement
    {
        $pdoStatement = $this->connexionBaseDeDonnees->getPdo()->prepare(
            "SELECT * FROM app_db WHERE $critere = :valeur"
        );
        $pdoStatement->execute(["valeur" => $valeur]);

        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if(!$data) {
            return null;
        }

        $depenses = [];
        foreach ($data as $depense) {
            $depenses[] = new Depense(
                id: $depense['idDepense'],
                titre: $depense['titreDepense'],
                date: new DateTime($depense['dateDepense']),
                montant: $depense['montantDepense'],
                payeur: new Utilisateur(
                    login: $depense['loginPayeur'],
                    nom: $depense['nomPayeur'],
                    prenom: $depense['prenomPayeur'],
                ),
                participants: Utilisateur::construireUtilisateursDepuisListe(json_decode($depense['participantsDepense'], true))
            );
        }

        $premiereLigne = $data[0];
        return new Evenement(
            id: $premiereLigne['idEvenement'],
            codeSecret: $premiereLigne['codeSecretEvenement'],
            titre: $premiereLigne['titreEvenement'],
            date: new DateTime($premiereLigne['dateEvenement']),
            proprietaire: new Utilisateur(
                login: $premiereLigne['loginProprietaire'],
                nom: $premiereLigne['nomProprietaire'],
                prenom: $premiereLigne['prenomProprietaire'],
                email: $premiereLigne['emailProprietaire'],
                mdpHache: $premiereLigne['mdpHacheProprietaire'],
                mdp: $premiereLigne['mdpProprietaire']
            ),
            membres: Utilisateur::construireUtilisateursDepuisListe(json_decode($premiereLigne['membresEvenement'], true)),
            depenses: $depenses
        );
    }

    public function recuperer($id) : ?Evenement
    {
       return $this->recupererPar("idEvenement", $id);
    }

    public function recupererParCodeSecret($code) : ?Evenement
    {
        return $this->recupererPar("codeSecretEvenement", $code);
    }

    /**
     * @return Evenement[]
     */
    public function recupererEvenementsUtilisateur($login) : array
    {
        $pdoStatement = $this->connexionBaseDeDonnees->getPdo()->prepare(
            "SELECT DISTINCT idEvenement, codeSecretEvenement, titreEvenement, dateEvenement,
                           loginProprietaire, nomProprietaire, prenomProprietaire, membresEvenement
                        FROM app_db
                        WHERE JSON_CONTAINS(membresEvenement, JSON_OBJECT('login', :login))"
        );
        $pdoStatement->execute(['login' => $login]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if(!$data) {
            return [];
        }
        $evenements = [];
        foreach ($data as $evenement) {
            $evenements[] = new Evenement(
                id: $evenement['idEvenement'],
                codeSecret: $evenement['codeSecretEvenement'] ?? null,
                titre: $evenement['titreEvenement'] ?? null,
                date: new DateTime($evenement['dateEvenement']),
                proprietaire: new Utilisateur(
                    login: $evenement['loginProprietaire']
                ),
                membres: Utilisateur::construireUtilisateursDepuisListe(json_decode($evenement['membresEvenement'], true))
            );
        }
        return $evenements;
    }

    public function mettreAJour(Evenement $evenement): void
    {
        $map = [
            "idEvenement" => $evenement->getId(),
            "codeSecretEvenement" => $evenement->getCodeSecret(),
            "titreEvenement" => $evenement->getTitre(),
            "dateEvenement" => $evenement->getDate()->format('Y-m-d H:i:s'),
            "membresEvenement" => Utilisateur::formatJsonListeUtilisateurs($evenement->getMembres())
        ];

        $nomsColonnes = array_keys($map);
        $setArray = array_map(function ($nomcolonne) {
            return "$nomcolonne=:$nomcolonne";
        }, $nomsColonnes);
        $setString = join(', ', $setArray);
        $sql = "UPDATE app_db SET $setString WHERE idEvenement=:idEvenement";
        $pdoStatement = $this->connexionBaseDeDonnees->getPdo()->prepare($sql);
        $pdoStatement->execute($map);
    }

    public function supprimer(int $id): bool
    {
        $sql = "DELETE FROM app_db WHERE idEvenement=:idEvenement";
        $pdoStatement = $this->connexionBaseDeDonnees->getPDO()->prepare($sql);
        $pdoStatement->execute(['idEvenement' => $id]);
        return ($pdoStatement->rowCount() > 0);
    }

    public function getNextId() : int
    {
        $query = $this->connexionBaseDeDonnees->getPdo()->query("SELECT MAX(idEvenement) FROM app_db");
        $query->execute();
        $obj = $query->fetch();
        return $obj[0] === null ? 0 : $obj[0] + 1;
    }

    public function compterNombreEvenementProprietaire($loginProprietaire): int
    {
        $sql = "SELECT COUNT(DISTINCT idEvenement) FROM app_db WHERE loginProprietaire=:loginProprietaire";
        $pdoStatement = $this->connexionBaseDeDonnees->getPDO()->prepare($sql);
        $pdoStatement->execute(["loginProprietaire" => $loginProprietaire]);
        $obj = $pdoStatement->fetch();
        return $obj[0] === null ? 0 : $obj[0];
    }
}