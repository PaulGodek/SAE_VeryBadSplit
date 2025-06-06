<?php

namespace App\VeryBadSplit\Modele\Repository;



use App\VeryBadSplit\Modele\DataObject\AbstractDataObject;

abstract class AbstractRepository
{
    public function mettreAJour(AbstractDataObject $objet): void
    {
        $leSet = [];
        foreach ($this->getNomsColonnes() as $attribut) {
            $leSet[] = $attribut . '= :' . $attribut . 'Tag';
        }

        $sql = 'UPDATE ' . $this->getNomTable() .
            ' SET ' . join(', ', $leSet) .
            ' WHERE ' . $this->getNomClePrimaire() . '= :' . $this->getNomClePrimaire() . 'Tag';


        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);
        $values = $this->formatTableauSQL($objet);
        $pdoStatement->execute($values);
    }



    public function ajouter(AbstractDataObject $objet): bool
    {

            $sql = 'INSERT INTO ' . $this->getNomTable() . ' (' . join(',', $this->getNomsColonnes()) . ') VALUES (:' . join("Tag, :", $this->getNomsColonnes()) . 'Tag)';
            $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);

            $values = $this->formatTableauSQL($objet);

            $pdoStatement->execute($values);



        return true;
    }

    public function ajouterJointure(AbstractDataObject $objet,string $login): bool
    {

         $sql='';

         if($this->getNomTable()=='Evenements'){
            $sql = 'INSERT INTO EtreMembre(idEvenement,loginMembre) VALUES (:id,:login)';

        }else if ($this->getNomTable()=='Depenses'){
             $sql = 'INSERT INTO Participer(idDepense,loginParticipant) VALUES (:id,:login)';
        }else{
             return true;
         }




        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);

        $pdoStatement->execute(['login' => $login,'id'=>$objet->getId()]);


        return true;
    }

    public function supprimerJointure(AbstractDataObject $objet,string $login): bool
    {

        $sql='';

        if($this->getNomTable()=='Evenements'){
            $sql = 'DELETE FROM EtreMembre(idEvenement,loginMembre) Where idEvenement=:id AND loginMembre=:login)';

        }else if ($this->getNomTable()=='Depenses'){
            $sql = 'DELETE FROM Participer(idDepense,loginMembre) Where idDepense=:id AND loginParticipant=:login)';
        }else{
            return true;
        }




        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);

        $pdoStatement->execute(['login' => $login, 'id' => $objet->getId()]);


        return true;
    }


    public function supprimer(string $clePrimaire): bool
    {

        $sql = "DELETE from " . $this->getNomTable() . " WHERE " . $this->getNomClePrimaire() . " = :clePrimaireTag";
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);
        $values = array();
        $values["clePrimaireTag"] = $clePrimaire;

        return $pdoStatement->execute($values);


    }

    public function recupererParClePrimaire(string $clePrimaire): ?AbstractDataObject
    {
        $sql = "SELECT * from " . $this->getNomTable() . " WHERE " . $this->getNomClePrimaire() . " = :clePrimaireTag";
        // Préparation de la requête
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->prepare($sql);


        $values = array(
            "clePrimaireTag" => $clePrimaire,

        );
        $pdoStatement->execute($values);

        $objetFormatTableau = $pdoStatement->fetch();

        if (!$objetFormatTableau) {
            return null;
        }
        return ($this->construireDepuisTableauSQL($objetFormatTableau));
    }

    /**
     * @return AbstractDataObject[]
     */
    public function recuperer(): ?AbstractDataObject

    {
        $objets = [];
        $pdoStatement = ConnexionBaseDeDonnees::getPdo()->query("SELECT * FROM " . $this->getNomTable());


        foreach ($pdoStatement as $objetFormatTableau) {
            $objet = $this->construireDepuisTableauSQL($objetFormatTableau);
            $objets[] = $objet;
        }
        return $objets;

    }


    protected abstract function getNomTable(): string;

    protected abstract function getNomClePrimaire(): string;

    protected abstract function construireDepuisTableauSQL(array $objetFormatTableau): AbstractDataObject;

    /** @return string[] */
    protected abstract function getNomsColonnes(): array;

    protected abstract function formatTableauSQL(AbstractDataObject $objet): array;

}
