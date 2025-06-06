<?php

namespace App\VeryBadSplit\Modele\DataObject;

use App\VeryBadSplit\Modele\Repository\DepenseRepository;
use DateTime;

class Evenement extends AbstractDataObject
{
    public function __construct(
        private int $id,
        private string $codeSecret ,
        private string $titre ,
        private DateTime $date ,
        private Utilisateur $proprietaire,
        private ?array $membres = [],
    ){
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCodeSecret(): ?string
    {
        return $this->codeSecret;
    }

    public function setCodeSecret(?string $codeSecret): void
    {
        $this->codeSecret = $codeSecret;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): void
    {
        $this->titre = $titre;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }


    public function setDate(?DateTime $date): void
    {
        $this->date = $date;
    }

    public function getproprietaire(): ?Utilisateur
    {
        return $this->proprietaire;
    }

    public function setproprietaire(Utilisateur $proprietaire): void
    {
        $this->proprietaire = $proprietaire;
    }

    /**
     * @return Utilisateur[]|null
     */
    public function getMembres(): ?array
    {
        return $this->membres;
    }

    /**
     * @param Utilisateur[]|null $membres
     */
    public function setMembres(?array $membres): void
    {
        $this->membres = $membres;
    }



    public function estProprietaire($login): bool {
        return $this->proprietaire->getLogin() === $login;
    }

    public function estMembre($login): bool {
        foreach ($this->membres as $membre) {
            if ($membre->getLogin() === $login) {
                return true;
            }
        }
        return false;
    }

    public function getNombreDeMembres() {
        return count($this->membres);
    }
}