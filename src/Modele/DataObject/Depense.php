<?php

namespace App\VeryBadSplit\Modele\DataObject;

use DateTime;

class Depense extends AbstractDataObject
{
    public function __construct(
        private int          $id,
        private string      $titre ,
        private DateTime    $date ,
        private float      $montant ,
        private Utilisateur $payeur ,
        private Evenement   $evenement ,
        private array       $participants = [],
    )
    {
    }

    public function getId(): int
    {
        return $this->id;
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

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(?float $montant): void
    {
        $this->montant = $montant;
    }

    public function getPayeur(): Utilisateur
    {
        return $this->payeur;
    }

    public function setpayeur(Utilisateur $payeur): void
    {
        $this->payeur = $payeur;
    }

    public function getEvenement(): Evenement
    {
        return $this->evenement;
    }

    public function setEvenement(Evenement $evenement): void
    {
        $this->evenement = $evenement;
    }

    /**
     * @return Utilisateur[]|null
     */
    public function getParticipants(): ?array
    {
        return $this->participants;
    }

    /**
     * @param Utilisateur[]|null $participants
     */
    public function setParticipants(?array $participants): void
    {
        $this->participants = $participants;
    }

    public function estPayeur($login): bool
    {
        return $this->payeur->getLogin() === $login;
    }

    public function estParticipant($login): bool {
        foreach ($this->participants as $participant) {
            if ($participant->getLogin() === $login) {
                return true;
            }
        }
        return false;
    }
}