<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use DateTime;
use InvalidArgumentException;
use PDO;
use PDOException;

class DepenseRepository extends AbstractRepository implements DepenseRepositoryInterface
{

    /**
     * @return Depense[]
     */
    public function recupererDepensesPayeesOuParticipeUtilisateur(string $login): array
    {
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            "SELECT * FROM ".$this->getNomTable()." d Join Participer p on p.idDepense = d.idDepense join Utilisateurs u on u.login=p.loginParticipant join Evenements e on e.idEvenement=d.idEvenement join EtreMembre em on em.idEvenement=e.idEvenement
                        WHERE   loginPayeur = '$login' OR p.loginParticipant = '$login'");
        $pdoStatement->execute(['login' => $login]);

        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if (!$data) {
            return [];
        }

        $participants = [];
        foreach ($data as $row) {
            if (!is_null($row['loginParticipant'])) {
                $participants[] = new Utilisateur(
                    $row['loginParticipant'],
                    $row['nom'],
                    $row['prenom'],
                    $row['email'],
                    $row['mdpHache']);
            }
        }
        $membres = [];
        foreach ($data as $row) {
            if (!is_null($row['loginMembre'])) {
                $membres[] = new Utilisateur(
                    $row['loginMembre'],
                    $row['nom'],
                    $row['prenom'],
                    $row['email'],
                    $row['mdpHache']);
            }

        }
        $premiereLigne=$data[0];

        $depenses=[];
        foreach ($data as $depense) {
            $depenses[] = new Depense(
                id: $depense['idDepense'],
                titre: $depense['titreDepense'],
                date: new DateTime($depense['dateDepense']),
                montant: $depense['montantDepense'],
                payeur: new Utilisateur(
                    $premiereLigne['loginPayeur'],
                    $premiereLigne['nom'],
                    $premiereLigne['prenom'],
                    $premiereLigne['email'],
                    $premiereLigne['mdpHache']),
                evenement:  new Evenement(
                    id: $premiereLigne['idEvenement'],
                    codeSecret: $premiereLigne['codeSecretEvenement'],
                    titre: $premiereLigne['titreEvenement'],
                    date: new DateTime($premiereLigne['dateEvenement']),
                    proprietaire: new Utilisateur(
                        $premiereLigne['loginProprietaire'],
                        $premiereLigne['nom'],
                        $premiereLigne['prenom'],
                        $premiereLigne['email'],
                        $premiereLigne['mdpHache']),
                    membres: $membres),
                participants: $participants
            );
        }
        return $depenses;
    }

    private function recupererPar($critere, $valeur) : ?Depense
    {

        if (!in_array($critere, $this->getNomsColonnes())) {
            throw new InvalidArgumentException("Critère de recherche invalide.");
        }

        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            "SELECT * FROM ".$this->getNomTable()." d Join Participer p on p.idDepense = d.idDepense join Utilisateurs u on u.login=p.loginParticipant join Evenements e on e.idEvenement=d.idEvenement join EtreMembre em on em.idEvenement=e.idEvenement
             WHERE d.$critere = :valeur"
        );
        $pdoStatement->execute([ "valeur"=>$valeur]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if(!$data) {
            return null;
        }

        $participants = [];
        foreach ($data as $row) {
            if (!is_null($row['loginParticipant'])) {
                $participants[] = new Utilisateur(
                    $row['loginParticipant'],
                    $row['nom'],
                    $row['prenom'],
                    $row['email'],
                    $row['mdpHache']);
            }
        }
        $premiereLigne=$data[0];
        $membres = [];
        foreach ($data as $row) {
                $membres[] = new Utilisateur(
                    $row['loginMembre'],
                    $row['nom'],
                    $row['prenom'],
                    $row['email'],
                    $row['mdpHache']);


        }


        return new Depense(
                id: $premiereLigne['idDepense'],
                titre: $premiereLigne['titreDepense'],
                date: new DateTime($premiereLigne['dateDepense']),
                montant: $premiereLigne['montantDepense'],
                payeur: new Utilisateur(
                    $premiereLigne['loginPayeur'],
                    $premiereLigne['nom'],
                    $premiereLigne['prenom'],
                    $premiereLigne['email'],
                    $premiereLigne['mdpHache']),
                evenement:  new Evenement(
                    id: $premiereLigne['idEvenement'],
                    codeSecret: $premiereLigne['codeSecretEvenement'],
                    titre: $premiereLigne['titreEvenement'],
                    date: new DateTime($premiereLigne['dateEvenement']),
                    proprietaire: new Utilisateur(
                        $premiereLigne['loginProprietaire'],
                        $premiereLigne['nom'],
                        $premiereLigne['prenom'],
                        $premiereLigne['email'],
                        $premiereLigne['mdpHache']),
                    membres: $membres),
                participants: $participants
            );
    }


    public function recupererParClePrimaire($id) : ?Depense
    {
        return $this->recupererPar("idDepense", $id);
    }

    public function recupererParEvenement($idEvenement): ?Depense
    {
        return $this->recupererPar('idEvenement', $idEvenement);
    }

    public function getNextId() : int
    {
        $query = ConnexionBaseDeDonnees::getPdo()->query("SELECT MAX(idDepense) FROM ".$this->getNomTable()."");
        $obj = $query->fetch();
        return $obj[0] === null ? 0 : $obj[0] + 1;
    }

    public function compterNombreDepensesEvenement($idEvenement): int
    {
        $sql = "SELECT COUNT(DISTINCT idDepense) FROM ".$this->getNomTable()." WHERE idEvenement=:idEvenement";
        $pdoStatement = ConnexionBaseDeDonnees::getPDO()->prepare($sql);
        $pdoStatement->execute(['idEvenement' => $idEvenement]);
        $obj = $pdoStatement->fetch();
        return $obj[0] === null ? 0 : $obj[0];
    }

    protected function getNomTable(): string
    {
       return "Depenses";
    }

    protected function getNomClePrimaire(): string
    {
        return "idDepense";
    }

    /**
     * @throws \Exception
     */
    protected function construireDepuisTableauSQL(array $depenseFormatTableau): AbstractDataObject
    {
        return new Depense($depenseFormatTableau['idDepense'],$depenseFormatTableau['titreDepense'],
            new DateTime($depenseFormatTableau['dateDepense']) ,$depenseFormatTableau['montantDepense'],
            $depenseFormatTableau['loginPayeur'],$depenseFormatTableau['idEvenement']

        );

    }

    protected function getNomsColonnes(): array
    {
        return ["idDepense","titreDepense", "dateDepense","montantDepense","loginPayeur","idEvenement"];
    }

    protected function formatTableauSQL(AbstractDataObject $depense): array
    {
        return array(
            "idDepenseTag" => $depense->getId(),
            "titreDepenseTag" => $depense->getTitre(),
            "dateDepenseTag" => $depense->getDate()->format('Y-m-d H:i:s'),
            "montantDepenseTag" => $depense->getMontant(),
            "loginPayeurTag" => $depense->getPayeur()->getLogin(),
            "idEvenementTag" => $depense->getEvenement()->getId(),


        );    }




}