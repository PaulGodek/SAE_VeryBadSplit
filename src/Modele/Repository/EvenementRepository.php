<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use DateTime;
use http\Message;
use InvalidArgumentException;
use PDO;

class EvenementRepository extends AbstractRepository implements EvenementRepositoryInterface
{
    private function recupererPar($critere, $valeur) : ?Evenement
    {

        if (!in_array($critere, $this->getNomsColonnes())) {
            throw new InvalidArgumentException("Critère de recherche invalide.");
        }

        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            $sql="SELECT * FROM ".$this->getNomTable()." e Join EtreMembre em on e.idEvenement=em.idEvenement join Utilisateurs u on u.login=em.loginMembre WHERE e.$critere = :valeur"
        );
        $pdoStatement->execute([ "valeur"=>$valeur]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if(!$data) {
            return null;
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
        $evenement=new Evenement(
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
            membres: $membres);






        return $evenement;
    }

    public function recupererParClePrimaire($id) : ?Evenement
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
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare(
            "SELECT *
                        FROM ".$this->getNomTable()." e join EtreMembre em on e.idEvenement=em.idEvenement join Utilisateurs on Utilisateurs.login=em.loginMembre
                        WHERE loginMembre= :login OR e.loginProprietaire= :login"
        );
        $pdoStatement->execute(['login' => $login]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if(!$data) {
            return [];
        }





        $membres = [];
        foreach ($data as $row) {
            if (!is_null($row['loginMembre'])) {
                $membres[] = $row['loginMembre'];
            }
        }


        $evenements=[] ;
        foreach ($data as $evenement){
            $evenements[]=new Evenement(
                id: $evenement['idEvenement'],
                codeSecret: $evenement['codeSecretEvenement'],
                titre: $evenement['titreEvenement'],
                date: new DateTime($evenement['dateEvenement']),
                proprietaire: new Utilisateur(
                    $evenement['loginProprietaire'],
                    $evenement['nom'],
                    $evenement['prenom'],
                    $evenement['email'],
                    $evenement['mdpHache']),
                membres: $membres);
            }

        return $evenements;
    }



    public function getNextId() : int
    {
        $query = ConnexionBaseDeDonnees::getPdo()->query("SELECT MAX(idEvenement) FROM ".$this->getNomTable());
        $query->execute();
        $obj = $query->fetch();
        return $obj[0] === null ? 0 : $obj[0] + 1;
    }

    public function compterNombreEvenementProprietaire($loginProprietaire): int
    {
        $sql = "SELECT COUNT(DISTINCT idEvenement) FROM ".$this->getNomTable()." WHERE loginProprietaire=:loginProprietaire";
        $pdoStatement = ConnexionBaseDeDonnees::getPDO()->prepare($sql);
        $pdoStatement->execute(["loginProprietaire" => $loginProprietaire]);
        $obj = $pdoStatement->fetch();
        return $obj[0] === null ? 0 : $obj[0];
    }


    protected function getNomTable(): string
    {
        return "Evenements";
    }

    protected function getNomClePrimaire(): string
    {
        return "idEvenement";
    }

    protected function construireDepuisTableauSQL(array $EvenementFormatTableau): AbstractDataObject
    {
        return new Evenement($EvenementFormatTableau['idEvenement'], $EvenementFormatTableau['codeSecretEvenement'],
            $EvenementFormatTableau['titreEvenement'], $EvenementFormatTableau['dateEvenement'],
            $EvenementFormatTableau['loginProprietaire'], $EvenementFormatTableau['membres']);

    }

    protected function getNomsColonnes(): array
    {
        return ["idEvenement", "codeSecretEvenement","titreEvenement", "dateEvenement","loginProprietaire"];
    }

    protected function formatTableauSQL(AbstractDataObject $evenement): array
    {
        return array(
            "idEvenementTag" => $evenement->getId(),
            "codeSecretEvenementTag" => $evenement->getCodeSecret(),
            "titreEvenementTag" => $evenement->getTitre(),
            "dateEvenementTag" => $evenement->getDate()->format('Y-m-d H:i:s'),
            "loginProprietaireTag" => $evenement->getProprietaire()->getLogin()



        );
    }


}