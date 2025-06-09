<?php

namespace App\VeryBadSplit\Modele\Repository;


use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\ConnexionBaseDeDonneesInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use DateTime;

use InvalidArgumentException;
use PDO;

class EvenementRepository extends AbstractRepository implements EvenementRepositoryInterface
{

    public function __construct(ConnexionBaseDeDonneesInterface $connexionBaseDeDonnees){
        parent::__construct($connexionBaseDeDonnees);
    }
    
    private function recupererPar($critere, $valeur): ?array
    {
        if (!in_array($critere, $this->getNomsColonnes())) {
            throw new InvalidArgumentException("Critère de recherche invalide.");
        }

        $sql = "
        SELECT 
            e.idEvenement, e.codeSecretEvenement, e.titreEvenement, e.dateEvenement, e.loginProprietaire,
            u.nom, u.prenom, u.email, u.mdpHache,
            em.loginMembre,
            um.nom as nomM, um.prenom as prenomM, um.email as emailM, um.mdpHache as mdpHacheM

        FROM " . $this->getNomTable() . " e
        JOIN Utilisateurs u ON u.login = e.loginProprietaire
        LEFT JOIN EtreMembre em ON em.idEvenement = e.idEvenement
        LEFT JOIN Utilisateurs um ON um.login = em.loginMembre
        WHERE e.$critere = :valeur
    ";

        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPdo()->prepare($sql);
        $pdoStatement->execute(["valeur" => $valeur]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $evenements = [];

        foreach ($data as $row) {
            $idEvenement = $row['idEvenement'];

            if (!isset($evenements[$idEvenement])) {
                $evenements[$idEvenement] = [
                    'evenement' => null,
                    'membres' => []
                ];

                $evenements[$idEvenement]['evenement'] = new Evenement(
                    id: $row['idEvenement'],
                    codeSecret: $row['codeSecretEvenement'],
                    titre: $row['titreEvenement'],
                    date: new DateTime($row['dateEvenement']),
                    proprietaire: new Utilisateur(
                        $row['loginProprietaire'],
                        $row['nom'],       // propriétaire
                        $row['prenom'],
                        $row['email'],
                        $row['mdpHache']
                    ),
                    membres: []
                );
            }

            // Ajout du membre (si présent)
            if (!empty($row['loginMembre'])) {
                $loginMembre = $row['loginMembre'];
                $membres = &$evenements[$idEvenement]['membres'];

                if (!isset($membres[$loginMembre])) {
                    $membres[$loginMembre] = new Utilisateur(
                        $loginMembre,
                        $row['nomM'],     // membre (même nom de colonne)
                        $row['prenomM'],
                        $row['emailM'],
                        $row['mdpHacheM']
                    );
                }
            }
        }

        // Finalisation : injecter les membres
        $resultats = [];
        foreach ($evenements as $info) {
            $evenement = $info['evenement'];
            $evenement->setMembres($info['membres']);
            $resultats[] = $evenement;
        }

        return $resultats;
    }

    public function recupererParClePrimaire($id) : ?Evenement
    {
       return ($this->recupererPar("idEvenement", $id))[0];
    }

    public function recupererParCodeSecret($code) : ?Evenement
    {
        return $this->recupererPar("codeSecretEvenement", $code)[0];
    }

    /**
     * @return Evenement[]
     */
    public function recupererEvenementsUtilisateur($login): array
    {
        $sql = "
        SELECT 
            e.idEvenement, e.codeSecretEvenement, e.titreEvenement, e.dateEvenement, e.loginProprietaire,
            u.nom, u.prenom, u.email, u.mdpHache,
            em.loginMembre,
            um.nom as nomM, um.prenom as prenomM, um.email as emailM, um.mdpHache as mdpHacheM

        FROM " . $this->getNomTable() . " e
        JOIN Utilisateurs u ON u.login = e.loginProprietaire
        LEFT JOIN EtreMembre em ON em.idEvenement = e.idEvenement
        LEFT JOIN Utilisateurs um ON um.login = em.loginMembre
        WHERE em.loginMembre = :login OR e.loginProprietaire = :login
    ";

        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPdo()->prepare($sql);
        $pdoStatement->execute(['login' => $login]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if (!$data) {
            return [];
        }

        $evenements = [];

        foreach ($data as $row) {
            $idEvenement = $row['idEvenement'];

            if (!isset($evenements[$idEvenement])) {
                $evenements[$idEvenement] = [
                    'evenement' => null,
                    'membres' => []
                ];

                $evenements[$idEvenement]['evenement'] = new Evenement(
                    id: $row['idEvenement'],
                    codeSecret: $row['codeSecretEvenement'],
                    titre: $row['titreEvenement'],
                    date: new DateTime($row['dateEvenement']),
                    proprietaire: new Utilisateur(
                        $row['loginProprietaire'],
                        $row['nom'],      // nom du propriétaire
                        $row['prenom'],
                        $row['email'],
                        $row['mdpHache']
                    ),
                    membres: [] // rempli après
                );
            }

            // Ajouter un membre s'il est présent
            if (!empty($row['loginMembre'])) {
                $loginMembre = $row['loginMembre'];
                $membres = &$evenements[$idEvenement]['membres'];
                if (!isset($membres[$loginMembre])) {
                    $membres[$loginMembre] = new Utilisateur(
                        $loginMembre,
                        $row['nomM'],
                        $row['prenomM'],
                        $row['emailM'],
                        $row['mdpHacheM']
                    );

                }
            }
        }

        // Injection finale des membres dans les événements
        $resultats = [];
        foreach ($evenements as $info) {
            $evenement = $info['evenement'];
            $evenement->setMembres($info['membres']);
            $resultats[] = $evenement;
        }

        return $resultats;
    }



    public function getNextId() : int
    {
        $query = $this->getConnexionBaseDeDonnees()->getPdo()->query("SELECT MAX(idEvenement) FROM ".$this->getNomTable());
        $query->execute();
        $obj = $query->fetch();
        return $obj[0] === null ? 0 : $obj[0] + 1;
    }

    public function compterNombreEvenementProprietaire($loginProprietaire): int
    {
        $sql = "SELECT COUNT(DISTINCT idEvenement) FROM ".$this->getNomTable()." WHERE loginProprietaire=:loginProprietaire";
        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPDO()->prepare($sql);
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