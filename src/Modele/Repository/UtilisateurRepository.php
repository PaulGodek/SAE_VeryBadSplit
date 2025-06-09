<?php

namespace App\VeryBadSplit\Modele\Repository;

use App\VeryBadSplit\Lib\MessageFlash;
use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\ConnexionBaseDeDonneesInterface;
use App\VeryBadSplit\Modele\Repository\Interface\UtilisateurRepositoryInterface;
use PDO;

class UtilisateurRepository extends AbstractRepository implements UtilisateurRepositoryInterface
{

    public function __construct(ConnexionBaseDeDonneesInterface $connexionBaseDeDonnees){
        parent::__construct($connexionBaseDeDonnees);
    }

    /**
     * @return Utilisateur|null
     */
    public function recupererParEmail($email) : ?Utilisateur {

        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPdo()->prepare(
            "SELECT *
                        FROM ". $this->getNomTable() ."
                        WHERE email = :email");
        $pdoStatement->execute(["email"=>$email]);
        $data = $pdoStatement->fetch();

        if(!$data) {
            return null;
        }

        $utilisateur = new Utilisateur(
        login: $data["login"],
        nom: $data["nom"],
        prenom: $data["prenom"],
        email: $data["email"],
         mdpHache: $data["mdpHache"]

        );

        return $utilisateur;
    }

    /**
     * @return Utilisateur[]
     */
    public function recupererUtilisateursOrdonnesPrenomNom() : array {
        $pdoStatement = $this->getConnexionBaseDeDonnees()->getPdo()->prepare(
            "SELECT *
                        FROM ". $this->getNomTable() ."
                        ORDER BY prenom, nom");
        $pdoStatement->execute();
        $data = $pdoStatement->fetchAll(PDO::FETCH_ASSOC);
        if(!$data) {
            return [];
        }
        $utilisateurs = [];
        foreach ($data as $utilisateur) {
            $utilisateurs[] = new Utilisateur(
                login: $utilisateur["login"],
                nom: $utilisateur["nom"],
                prenom: $utilisateur["prenom"],
                email: $utilisateur["email"],
                mdpHache: $utilisateur["mdpHache"]);
        }
        return $utilisateurs;
    }


    protected function getNomTable(): string
    {
        return "Utilisateurs";
    }

    protected function getNomClePrimaire(): string
    {
        return "login";
    }

    protected function construireDepuisTableauSQL(array $utilisateurFormatTableau): Utilisateur
    {
        return new Utilisateur($utilisateurFormatTableau['login'],
            $utilisateurFormatTableau['nom'], $utilisateurFormatTableau['prenom'],
            $utilisateurFormatTableau['email'],$utilisateurFormatTableau['mdpHache']
        );
    }

    protected function getNomsColonnes(): array
    {
        return ["login", "nom", "prenom","email","mdpHache"];
    }

    protected function formatTableauSQL(AbstractDataObject $utilisateur): array
    {
        return array(
            "loginTag" => $utilisateur->getLogin(),
            "nomTag" => $utilisateur->getNom(),
            "prenomTag" => $utilisateur->getPrenom(),
            "emailTag" => $utilisateur->getEmail(),
            "mdpHacheTag" => $utilisateur->getmdpHache(),
        );
    }
}