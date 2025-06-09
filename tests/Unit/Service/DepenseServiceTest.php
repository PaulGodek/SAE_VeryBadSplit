<?php

namespace Tests\Unit\Service;

use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\UtilisateurRepositoryInterface;
use App\VeryBadSplit\Service\DepenseService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\Interface\EvenementServiceInterface;
use Tests\Unit\TestCase;

class DepenseServiceTest extends TestCase
{
    private DepenseService $service;
    private $depenseRepositoryMock;
    private $utilisateurRepositoryMock;
    private $evenementRepositoryMock;
    private $evenementServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connecterUtilisateur('alice');
        
        $this->depenseRepositoryMock = $this->createMock(DepenseRepositoryInterface::class);
        $this->utilisateurRepositoryMock = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->evenementRepositoryMock = $this->createMock(EvenementRepositoryInterface::class);
        $this->evenementServiceMock = $this->createMock(EvenementServiceInterface::class);
        
        $this->service = new DepenseService(
            $this->depenseRepositoryMock,
            $this->utilisateurRepositoryMock,
            $this->evenementRepositoryMock,
            $this->evenementServiceMock
        );
    }

    public function testVerifierAccesDepenseAvecAcces(): void
    {
        $idDepense = 1;

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);

        $resultat = $this->service->verifierAccesDepense($idDepense);

        $this->assertSame($depense, $resultat);
    }

    public function testVerifierAccesDepenseInexistante(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Dépense inexistante.");

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->service->verifierAccesDepense(1);
    }

    public function testVerifierAccesDepenseSansAcces(): void
    {
        $idDepense = 1;

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(false);
        $evenement->method('getCodeSecret')->willReturn('secret123');
        $evenement->method('getId')->willReturn(1);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Vous n'avez pas de droits d'éditions sur cet événement.");

        $this->service->verifierAccesDepense($idDepense);
    }

    public function testCreerDepenseAvecSucces(): void
    {
        $idEvenement = 1;
        $titre = "Restaurant";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice", "bob"];

        $alice = $this->createMock(Utilisateur::class);
        $alice->method('getLogin')->willReturn('alice');
        
        $bob = $this->createMock(Utilisateur::class);
        $bob->method('getLogin')->willReturn('bob');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('secret123');

        $this->evenementServiceMock->method('verifierAccesEvenement')->willReturn($evenement);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')
            ->willReturnMap([
                ['alice', $alice],
                ['bob', $bob]
            ]);

        $this->depenseRepositoryMock->method('getNextId')->willReturn(1);
        $this->depenseRepositoryMock->method('ajouter')->willReturn(true);
        $this->depenseRepositoryMock->method('ajouterJointure')->willReturn(true);

        $resultat = $this->service->creerDepense($idEvenement, $titre, $montant, $payeurLogin, $loginsParticipants);
        
        $this->assertEquals('secret123', $resultat);
    }

    public function testCreerDepenseTailleTitreInvalide(): void
    {
        $idEvenement = 1;
        $titre = str_repeat('a', 51); // Titre trop long
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice"];

        $evenement = $this->createMock(Evenement::class);
        $this->evenementServiceMock->method('verifierAccesEvenement')->willReturn($evenement);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du titre n'est pas valide.");

        $this->service->creerDepense($idEvenement, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testCreerDepenseTitreVide(): void
    {
        $idEvenement = 1;
        $titre = "";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice"];

        $evenement = $this->createMock(Evenement::class);
        $this->evenementServiceMock->method('verifierAccesEvenement')->willReturn($evenement);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Attributs manquants.");

        $this->service->creerDepense($idEvenement, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testCreerDepenseSansParticipants(): void
    {
        $idEvenement = 1;
        $titre = "Restaurant";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = [];

        $evenement = $this->createMock(Evenement::class);
        $this->evenementServiceMock->method('verifierAccesEvenement')->willReturn($evenement);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Il faut au moins un participant.");

        $this->service->creerDepense($idEvenement, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testCreerDepensePayeurNonMembre(): void
    {
        $idEvenement = 1;
        $titre = "Restaurant";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice"];

        $alice = $this->createMock(Utilisateur::class);
        $alice->method('getLogin')->willReturn('alice');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->with('alice')->willReturn(false);

        $this->evenementServiceMock->method('verifierAccesEvenement')->willReturn($evenement);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with('alice')->willReturn($alice);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le payeur n'existe pas ou n'est pas membre de l'événement.");

        $this->service->creerDepense($idEvenement, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testCreerDepenseParticipantNonMembre(): void
    {
        $idEvenement = 1;
        $titre = "Restaurant";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice", "bob"];

        $alice = $this->createMock(Utilisateur::class);
        $alice->method('getLogin')->willReturn('alice');
        
        $bob = $this->createMock(Utilisateur::class);
        $bob->method('getLogin')->willReturn('bob');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')
            ->willReturnMap([
                ['alice', true],
                ['bob', false]
            ]);

        $this->evenementServiceMock->method('verifierAccesEvenement')->willReturn($evenement);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')
            ->willReturnMap([
                ['alice', $alice],
                ['bob', $bob]
            ]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Un des participants n'existe pas ou n'est pas membre de l'événement.");

        $this->service->creerDepense($idEvenement, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testMettreAJourDepenseAvecSucces(): void
    {
        $idDepense = 1;
        $titre = "Restaurant modifié";
        $montant = 75.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice", "bob"];

        $alice = $this->createMock(Utilisateur::class);
        $alice->method('getLogin')->willReturn('alice');
        
        $bob = $this->createMock(Utilisateur::class);
        $bob->method('getLogin')->willReturn('bob');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('secret123');

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('getParticipants')->willReturn([]);
        $depense->method('estParticipant')->willReturn(false);
        $depense->expects($this->once())->method('setTitre')->with($titre);
        $depense->expects($this->once())->method('setMontant')->with($montant);
        $depense->expects($this->once())->method('setPayeur')->with($alice);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementServiceMock->method('verifierDroitsEvenement');
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')
            ->willReturnMap([
                ['alice', $alice],
                ['bob', $bob]
            ]);
        $this->depenseRepositoryMock->expects($this->once())->method('mettreAJour');

        $result = $this->service->mettreAJourDepense($idDepense, $titre, $montant, $payeurLogin, $loginsParticipants);
        
        $this->assertEquals('secret123', $result);
    }

    public function testMettreAJourDepenseInexistante(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Dépense inexistante.");

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->service->mettreAJourDepense(1, "Titre", 50.0, "alice", ["alice"]);
    }

    public function testMettreAJourDepenseAttributsManquants(): void
    {
        $depense = $this->createMock(Depense::class);
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);

        $evenement = $this->createMock(Evenement::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $this->evenementServiceMock->method('verifierAccesEvenement')->willReturn($evenement);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Attributs manquants");
        $this->service->mettreAJourDepense(0, "", 0, "", []);
    }

    public function testMettreAJourDepenseTailleTitreInvalide(): void
    {
        $idDepense = 1;
        $titre = str_repeat('a', 51); // Titre trop long
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice"];

        $evenement = $this->createMock(Evenement::class);
        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementServiceMock->method('verifierDroitsEvenement');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du titre n'est pas valide.");

        $this->service->mettreAJourDepense($idDepense, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testMettreAJourDepenseSansParticipants(): void
    {
        $idDepense = 1;
        $titre = "Restaurant";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = [];

        $alice = $this->createMock(Utilisateur::class);
        $alice->method('getLogin')->willReturn('alice');

        $participant1 = $this->createMock(Utilisateur::class);
        $participant1->method('getLogin')->willReturn('participant1');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('secret123');

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('getParticipants')->willReturn([$participant1]);
        $depense->expects($this->once())->method('setTitre')->with($titre);
        $depense->expects($this->once())->method('setMontant')->with($montant);
        $depense->expects($this->once())->method('setPayeur')->with($alice);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementServiceMock->method('verifierDroitsEvenement');
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')
            ->willReturnMap([
                ['alice', $alice],
                ['participant1', $participant1]
            ]);
        $this->depenseRepositoryMock->expects($this->once())->method('mettreAJour');

        $result = $this->service->mettreAJourDepense($idDepense, $titre, $montant, $payeurLogin, $loginsParticipants);
        
        $this->assertEquals('secret123', $result);
    }

    public function testMettreAJourDepensePayeurNonMembre(): void
    {
        $idDepense = 1;
        $titre = "Restaurant";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice"];

        $alice = $this->createMock(Utilisateur::class);
        $alice->method('getLogin')->willReturn('alice');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->with('alice')->willReturn(false);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementServiceMock->method('verifierDroitsEvenement');
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with('alice')->willReturn($alice);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le payeur n'existe pas ou n'est pas membre de l'événement.");

        $this->service->mettreAJourDepense($idDepense, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testMettreAJourDepenseParticipantNonMembre(): void
    {
        $idDepense = 1;
        $titre = "Restaurant";
        $montant = 50.0;
        $payeurLogin = "alice";
        $loginsParticipants = ["alice", "bob"];

        $alice = $this->createMock(Utilisateur::class);
        $alice->method('getLogin')->willReturn('alice');
        
        $bob = $this->createMock(Utilisateur::class);
        $bob->method('getLogin')->willReturn('bob');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')
            ->willReturnMap([
                ['alice', true],
                ['bob', false]
            ]);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('getParticipants')->willReturn([]);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementServiceMock->method('verifierDroitsEvenement');
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')
            ->willReturnMap([
                ['alice', $alice],
                ['bob', $bob]
            ]);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Un des participants n'existe pas ou n'est pas membre de l'événement.");

        $this->service->mettreAJourDepense($idDepense, $titre, $montant, $payeurLogin, $loginsParticipants);
    }

    public function testSupprimerDepenseAvecSucces(): void
    {
        $idDepense = 1;

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('getCodeSecret')->willReturn('secret123');
        $evenement->method('getId')->willReturn(1);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $this->evenementServiceMock->method('verifierDroitsEvenement');
        $this->depenseRepositoryMock->method('supprimer')->willReturn(true);

        $result = $this->service->supprimerDepense($idDepense);
        
        $this->assertEquals('secret123', $result);
    }

    public function testSupprimerDepenseInexistante(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Dépense inexistante.");

        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->service->supprimerDepense(1);
    }

    public function testRecupererDepensesParEvenement(): void
    {
        $idEvenement = 1;
        $depenses = [
            $this->createMock(Depense::class),
            $this->createMock(Depense::class)
        ];

        $this->depenseRepositoryMock->method('recupererParEvenement')->with($idEvenement)->willReturn($depenses);

        $result = $this->service->recupererDepensesParEvenement($idEvenement);
        
        $this->assertSame($depenses, $result);
    }

    public function testSupprimerParticipantAvecSucces(): void
    {
        $idDepense = 1;
        $loginUtilisateur = "bob";

        $utilisateur = $this->createMock(Utilisateur::class);
        $utilisateur->method('getLogin')->willReturn('bob');

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('getCodeSecret')->willReturn('secret123');

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('estParticipant')->with($loginUtilisateur)->willReturn(true);
        $depense->method('getParticipants')->willReturn([
            $utilisateur,
            $this->createMock(Utilisateur::class)
        ]);

        // Mock pour verifierAccesDepense
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($loginUtilisateur)->willReturn($utilisateur);
        $this->depenseRepositoryMock->method('supprimerJointure')->willReturn(true);
        $this->depenseRepositoryMock->expects($this->once())->method('mettreAJour');

        $result = $this->service->supprimerParticipant($idDepense, $loginUtilisateur);
        
        $this->assertEquals('secret123', $result);
    }

    public function testSupprimerParticipantDernierParticipant(): void
    {
        $idDepense = 1;
        $loginUtilisateur = "bob";

        $utilisateur = $this->createMock(Utilisateur::class);

        $evenement = $this->createMock(Evenement::class);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('estParticipant')->with($loginUtilisateur)->willReturn(true);
        $depense->method('getParticipants')->willReturn([$utilisateur]); // Un seul participant

        // Mock pour verifierAccesDepense
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($loginUtilisateur)->willReturn($utilisateur);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Cet utilisateur est le dernier participant, vous ne pouvez pas le supprimer.");

        $this->service->supprimerParticipant($idDepense, $loginUtilisateur);
    }

    public function testSupprimerParticipantUtilisateurInexistant(): void
    {
        $idDepense = 1;
        $loginUtilisateur = "bob";

        $evenement = $this->createMock(Evenement::class);
        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);

        // Mock pour verifierAccesDepense
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($loginUtilisateur)->willReturn(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Utilisateur inexistant.");

        $this->service->supprimerParticipant($idDepense, $loginUtilisateur);
    }

    public function testSupprimerParticipantNonParticipant(): void
    {
        $idDepense = 1;
        $loginUtilisateur = "bob";

        $utilisateur = $this->createMock(Utilisateur::class);
        $evenement = $this->createMock(Evenement::class);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('estParticipant')->with($loginUtilisateur)->willReturn(false);

        // Mock pour verifierAccesDepense
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($loginUtilisateur)->willReturn($utilisateur);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Cet utilisateur ne participe pas à la dépense.");

        $this->service->supprimerParticipant($idDepense, $loginUtilisateur);
    }

    public function testAjouterParticipantAvecSucces(): void
    {
        $idDepense = 1;
        $loginUtilisateur = "bob";

        $utilisateur = $this->createMock(Utilisateur::class);

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('getCodeSecret')->willReturn('secret123');

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('estParticipant')->with($loginUtilisateur)->willReturn(false);

        // Mock pour verifierAccesDepense
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($loginUtilisateur)->willReturn($utilisateur);
        $this->depenseRepositoryMock->method('ajouterJointure')->willReturn(true);
        $this->depenseRepositoryMock->expects($this->once())->method('mettreAJour');

        $result = $this->service->ajouterParticipant($idDepense, $loginUtilisateur);
        
        $this->assertEquals('secret123', $result);
    }

    public function testAjouterParticipantDejaParticipant(): void
    {
        $idDepense = 1;
        $loginUtilisateur = "bob";

        $utilisateur = $this->createMock(Utilisateur::class);

        $evenement = $this->createMock(Evenement::class);

        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);
        $depense->method('estParticipant')->with($loginUtilisateur)->willReturn(true);

        // Mock pour verifierAccesDepense
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($loginUtilisateur)->willReturn($utilisateur);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Cet utilisateur participe déjà à la dépense");

        $this->service->ajouterParticipant($idDepense, $loginUtilisateur);
    }

    public function testAjouterParticipantUtilisateurInexistant(): void
    {
        $idDepense = 1;
        $loginUtilisateur = "bob";

        $evenement = $this->createMock(Evenement::class);
        $depense = $this->createMock(Depense::class);
        $depense->method('getEvenement')->willReturn($evenement);

        // Mock pour verifierAccesDepense
        $this->depenseRepositoryMock->method('recupererParClePrimaire')->willReturn($depense);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getId')->willReturn(1);

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($loginUtilisateur)->willReturn(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Utilisateur inexistant.");

        $this->service->ajouterParticipant($idDepense, $loginUtilisateur);
    }
}