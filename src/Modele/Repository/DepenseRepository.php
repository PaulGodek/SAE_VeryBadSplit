<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\ConnexionBaseDeDonneesInterface;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use DateTime;
use InvalidArgumentException;
use PDO;

class DepenseRepository extends AbstractRepository implements DepenseRepositoryInterface
{

    public function __construct(ConnexionBaseDeDonneesInterface $connexionBaseDeDonnees){
        parent::__construct($connexionBaseDeDonnees);
    }

    /**
     * @return Depense[]
     */
    public function recupererDepensesPayeesOuParticipeUtilisateur(string $login): array
    {
        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPdo()->prepare(
            "SELECT d.*, 
                    upay.nom , upay.prenom, upay.email , upay.mdpHache,
        
                    p.loginParticipant,
                    up.nom as nomPA, up.prenom as prenomPA, up.email as emailPA, up.mdpHache as mdpHachePA,
        
                    e.idEvenement, e.codeSecretEvenement, e.titreEvenement, e.dateEvenement, e.loginProprietaire,
                    
                    em.loginMembre,
                    um.nom as nomM, um.prenom as prenomM, um.email as emailM, um.mdpHache as mdpHacheM,
        
                    uprop.nom as nomP, uprop.prenom as prenomP, uprop.email as emailP, uprop.mdpHache as mdpHacheP
                    from ".$this->getNomTable()." d
                    JOIN Utilisateurs upay ON d.loginPayeur = upay.login
                    JOIN Evenements e ON e.idEvenement = d.idEvenement
                    JOIN Utilisateurs uprop ON uprop.login = e.loginProprietaire
                    LEFT JOIN Participer p ON p.idDepense = d.idDepense
                    LEFT JOIN Utilisateurs up ON up.login = p.loginParticipant
                    LEFT JOIN EtreMembre em ON em.idEvenement = e.idEvenement
                    LEFT JOIN Utilisateurs um ON um.login = em.loginMembre
                    WHERE   loginPayeur =:login OR p.loginParticipant =:login ");
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
                    $row['nomPA'],
                    $row['prenomPA'],
                    $row['emailPA'],
                    $row['mdpHachePA']);
            }
        }

        $membres = [];
        foreach ($data as $row) {
            if (!is_null($row['loginMembre'])) {
                $membres[] = new Utilisateur(
                    $row['loginMembre'],
                    $row['nomM'],
                    $row['prenomM'],
                    $row['emailM'],
                    $row['mdpHacheM']);
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
                        $premiereLigne['nomP'],
                        $premiereLigne['prenomP'],
                        $premiereLigne['emailP'],
                        $premiereLigne['mdpHacheP']),
                    membres: $membres),
                participants: $participants
            );
        }
        return $depenses;
    }

    private function recupererDepensesPar($critere, $valeur): ?array
    {
        if (!in_array($critere, $this->getNomsColonnes())) {
            throw new InvalidArgumentException("Critère de recherche invalide.");
        }

        $sql = "
        SELECT 
            d.*, 
            upay.nom , upay.prenom, upay.email , upay.mdpHache,

            p.loginParticipant,
            up.nom as nomPA, up.prenom as prenomPA, up.email as emailPA, up.mdpHache as mdpHachePA,

            e.idEvenement, e.codeSecretEvenement, e.titreEvenement, e.dateEvenement, e.loginProprietaire,
            
            em.loginMembre,
            um.nom as nomM, um.prenom as prenomM, um.email as emailM, um.mdpHache as mdpHacheM,

            uprop.nom as nomP, uprop.prenom as prenomP, uprop.email as emailP, uprop.mdpHache as mdpHacheP

        FROM " . $this->getNomTable() . " d
        JOIN Utilisateurs upay ON d.loginPayeur = upay.login
        JOIN Evenements e ON e.idEvenement = d.idEvenement
        JOIN Utilisateurs uprop ON uprop.login = e.loginProprietaire
        LEFT JOIN Participer p ON p.idDepense = d.idDepense
        LEFT JOIN Utilisateurs up ON up.login = p.loginParticipant
        LEFT JOIN EtreMembre em ON em.idEvenement = e.idEvenement
        LEFT JOIN Utilisateurs um ON um.login = em.loginMembre

        WHERE d.$critere = :valeur
    ";

        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPdo()->prepare($sql);
        $pdoStatement->execute(["valeur" => $valeur]);
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        $depenses = [];

        foreach ($data as $row) {
            $idDepense = $row['idDepense'];

            if (!isset($depenses[$idDepense])) {
                $depenses[$idDepense] = [
                    'participants' => [],
                    'membres' => [],
                    'depense' => null
                ];

                $depenses[$idDepense]['depense'] = new Depense(
                    id: $row['idDepense'],
                    titre: $row['titreDepense'],
                    date: new DateTime($row['dateDepense']),
                    montant: $row['montantDepense'],
                    payeur: new Utilisateur(
                        $row['loginPayeur'],
                        $row['nom'],
                        $row['prenom'],
                        $row['email'],
                        $row['mdpHache']
                    ),
                    evenement: new Evenement(
                        id: $row['idEvenement'],
                        codeSecret: $row['codeSecretEvenement'],
                        titre: $row['titreEvenement'],
                        date: new DateTime($row['dateEvenement']),
                        proprietaire: new Utilisateur(
                            $row['loginProprietaire'],
                            $row['nomP'],
                            $row['prenomP'],
                            $row['emailP'],
                            $row['mdpHacheP']
                        ),
                        membres: []
                    ),
                    participants: []
                );
            }

            // Ajout participant (si présent et pas encore ajouté)
            if (!empty($row['loginParticipant'])) {
                $login = $row['loginParticipant'];
                $participants = &$depenses[$idDepense]['participants'];

                if (!isset($participants[$login])) {
                    $participants[$login] = new Utilisateur(
                        $login,
                        $row['nomPA'],
                        $row['prenomPA'],
                        $row['emailPA'],
                        $row['mdpHachePA']
                    );
                }
            }

            // Ajout membre (si présent et pas encore ajouté)
            if (!empty($row['loginMembre'])) {
                $login = $row['loginMembre'];
                $membres = &$depenses[$idDepense]['membres'];

                if (!isset($membres[$login])) {
                    $membres[$login] = new Utilisateur(
                        $login,
                        $row['nomM'],
                        $row['prenomM'],
                        $row['emailM'],
                        $row['mdpHacheM']
                    );
                }
            }
        }

        // Finalisation : injecter les listes dans les objets
        $resultats = [];

        foreach ($depenses as $info) {
            $depense = $info['depense'];
            $depense->setParticipants($info['participants']);
            $depense->getEvenement()->setMembres($info['membres']);
            $resultats[] = $depense;
        }

        return $resultats;
    }


    public function recupererParClePrimaire($id) : ?Depense
    {
        return $this->recupererDepensesPar("idDepense", $id)[0];
    }

    public function recupererParEvenement($idEvenement): ?array
    {
        return $this->recupererDepensesPar('idEvenement', $idEvenement);
    }

    public function getNextId() : int
    {
        $query = $this->getConnexionBaseDeDonnees()->getPdo()->query("SELECT MAX(idDepense) FROM ".$this->getNomTable()."");
        $obj = $query->fetch();
        return $obj[0] === null ? 0 : $obj[0] + 1;
    }

    public function compterNombreDepensesEvenement($idEvenement): int
    {
        $sql = "SELECT COUNT(DISTINCT idDepense) FROM ".$this->getNomTable()." WHERE idEvenement=:idEvenement";
        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPDO()->prepare($sql);
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