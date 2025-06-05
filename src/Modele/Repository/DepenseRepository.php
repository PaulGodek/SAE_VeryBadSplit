<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use DateTime;
use PDO;
use PDOException;

class DepenseRepository implements DepenseRepositoryInterface
{
    public function ajouter(Depense $depense): bool
    {
        $map = [
            "loginProprietaire" => $depense->getEvenement()->getProprietaire()->getLogin(),
            "nomProprietaire" => $depense->getEvenement()->getProprietaire()->getNom(),
            "prenomProprietaire" => $depense->getEvenement()->getProprietaire()->getPrenom(),
            "emailProprietaire" => $depense->getEvenement()->getProprietaire()->getEmail(),
            "mdpHacheProprietaire" => $depense->getEvenement()->getProprietaire()->getMdpHache(),
            "mdpProprietaire" => $depense->getEvenement()->getProprietaire()->getMdp(),
            "idEvenement" => $depense->getEvenement()->getId(),
            "codeSecretEvenement" => $depense->getEvenement()->getCodeSecret(),
            "titreEvenement" => $depense->getEvenement()->getTitre(),
            "dateEvenement" => $depense->getEvenement()->getDate()->format('Y-m-d H:i:s'),
            "membresEvenement" => Utilisateur::formatJsonListeUtilisateurs($depense->getEvenement()->getMembres()),
            "idDepense" => $depense->getId(),
            "titreDepense" => $depense->getTitre(),
            "dateDepense" => $depense->getDate()->format('Y-m-d H:i:s'),
            "montantDepense" => $depense->getMontant(),
            "loginPayeur" => $depense->getPayeur()->getLogin(),
            "nomPayeur" => $depense->getPayeur()->getNom(),
            "prenomPayeur" => $depense->getPayeur()->getPrenom(),
            "participantsDepense" => Utilisateur::formatJsonListeUtilisateurs($depense->getParticipants())
        ];

        $nomsColonnes = array_keys($map);
        $insertString = '(' . join(', ', $nomsColonnes) . ')';

        $partiesValues = array_map(function ($nomcolonne) {
            return ":{$nomcolonne}";
        }, $nomsColonnes);
        $valueString = '(' . join(', ', $partiesValues) . ')';

        $sql = "INSERT INTO app_db $insertString VALUES $valueString";
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);

        try {
            $pdoStatement->execute($map);
            return true;
        } catch (PDOException $exception) {
            if ($pdoStatement->errorCode() === "23000") {
                return false;
            } else {
                throw $exception;
            }
        }
    }

    public function recuperer(int $id): ?Depense
    {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            "SELECT idDepense, titreDepense, dateDepense, montantDepense, loginPayeur, nomPayeur, prenomPayeur, participantsDepense,
                           idEvenement, codeSecretEvenement, membresEvenement, loginProprietaire, nomProprietaire, prenomProprietaire,
                           loginProprietaire
                        FROM app_db
                        WHERE idDepense = :idDepense");
        $pdoStatement->execute(['idDepense' => $id]);
        $data = $pdoStatement->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return null;
        }
        return new Depense(
            id: $data['idDepense'],
            titre: $data['titreDepense'],
            date: new DateTime($data['dateDepense']),
            montant: $data['montantDepense'],
            payeur: new Utilisateur(
                login: $data['loginPayeur'],
                nom: $data['nomPayeur'],
                prenom: $data['prenomPayeur'],
            ),
            evenement: new Evenement(
                id: $data['idEvenement'],
                codeSecret: $data['codeSecretEvenement'],
                proprietaire: new Utilisateur(
                    login: $data['loginProprietaire'],
                    nom: $data['nomProprietaire'],
                    prenom: $data['prenomProprietaire']
                ),
                membres: Utilisateur::construireUtilisateursDepuisListe(json_decode($data['membresEvenement'], true))
            ),
            participants: Utilisateur::construireUtilisateursDepuisListe(json_decode($data['participantsDepense'], true))
        );
    }

    /**
     * @return Depense[]
     */
    public function recupererDepensesPayeesOuParticipeUtilisateur(string $login): array
    {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            "SELECT DISTINCT idDepense, titreDepense, dateDepense, montantDepense, 
                                   loginPayeur, nomPayeur, prenomPayeur, 
                                   participantsDepense
                        FROM app_db
                        WHERE JSON_CONTAINS(participantsDepense, JSON_OBJECT('login', :login)) OR loginPayeur = '$login'");
        $pdoStatement->execute(['login' => $login]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if (!$data) {
            return [];
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
        return $depenses;
    }
    
    public function mettreAJour(Depense $depense): void
    {
        $map = [
            "idDepense" => $depense->getId(),
            "titreDepense" => $depense->getTitre(),
            "dateDepense" => $depense->getDate()->format('Y-m-d H:i:s'),
            "montantDepense" => $depense->getMontant(),
            "loginPayeur" => $depense->getPayeur()->getLogin(),
            "nomPayeur" => $depense->getPayeur()->getNom(),
            "prenomPayeur" => $depense->getPayeur()->getPrenom(),
            "participantsDepense" => Utilisateur::formatJsonListeUtilisateurs($depense->getParticipants())
        ];

        $nomsColonnes = array_keys($map);
        $setArray = array_map(function ($nomcolonne) {
            return "$nomcolonne=:{$nomcolonne}";
        }, $nomsColonnes);
        $setString = join(', ', $setArray);
        $sql = "UPDATE app_db SET $setString WHERE idDepense=:idDepense";
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);
        $pdoStatement->execute($map);
    }

    public function supprimer(int $id): bool
    {
        $sql = "DELETE FROM app_db WHERE idDepense=:idDepense";
        $pdoStatement = ConnexionBaseDeDonnees::getPDO()->prepare($sql);
        $pdoStatement->execute(["idDepense" => $id]);
        return ($pdoStatement->rowCount() > 0);
    }

    public function getNextId() : int
    {
        $query = ConnexionBaseDeDonnees::getPdo()->query("SELECT MAX(idDepense) FROM app_db");
        $obj = $query->fetch();
        return $obj[0] === null ? 0 : $obj[0] + 1;
    }

    public function compterNombreDepensesEvenement($idEvenement): int
    {
        $sql = "SELECT COUNT(DISTINCT idDepense) FROM app_db WHERE idEvenement=:idEvenement";
        $pdoStatement = ConnexionBaseDeDonnees::getPDO()->prepare($sql);
        $pdoStatement->execute(['idEvenement' => $idEvenement]);
        $obj = $pdoStatement->fetch();
        return $obj[0] === null ? 0 : $obj[0];
    }
}