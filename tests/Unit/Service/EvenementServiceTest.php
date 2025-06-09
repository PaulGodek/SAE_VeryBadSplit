<?php

namespace Tests\Unit\Service;

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\UtilisateurRepositoryInterface;
use App\VeryBadSplit\Service\EvenementService;
use App\VeryBadSplit\Service\Exception\ServiceException;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\Unit\TestCase;

class EvenementServiceTest extends TestCase
{
    private EvenementService $service;
    private $evenementRepositoryMock;
    private $utilisateurRepositoryMock;
    private $depenseRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->evenementRepositoryMock = $this->createMock(EvenementRepositoryInterface::class);
        $this->utilisateurRepositoryMock = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->depenseRepositoryMock = $this->createMock(DepenseRepositoryInterface::class);
        
        $this->service = new EvenementService(
            $this->evenementRepositoryMock,
            $this->utilisateurRepositoryMock,
            $this->depenseRepositoryMock
        );

        $this->connecterUtilisateur('alice');
    }
    
    public function testVerifierExistenceEvenementInexistant(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Événement inexistant");

        $this->service->verifierExistenceEvenement(null);
    }

    public function testVerifierAccesEvenementAvecAcces(): void
    {
        $idEvenement = 1;

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->with('alice')->willReturn(true);

        $this->evenementRepositoryMock->method('recupererParClePrimaire')->with($idEvenement)->willReturn($evenement);

        $resultat = $this->service->verifierAccesEvenement($idEvenement);

        $this->assertSame($evenement, $resultat);
    }

    public function testVerifierAccesEvenementSansAcces(): void
    {
        $idEvenement = 1;

        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->with('alice')->willReturn(false);
        $evenement->method('getCodeSecret')->willReturn('XYZ789');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Vous n'avez pas de droits d'éditions sur cet événement");

        $this->evenementRepositoryMock->method('recupererParClePrimaire')->with($idEvenement)->willReturn($evenement);

        $this->service->verifierAccesEvenement($idEvenement);
    }
    
    public function testVerifierAccesPropietaireEvenementSansAcces(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estProprietaire')->with('alice')->willReturn(false);

        $this->evenementRepositoryMock->method('recupererParClePrimaire')->with(1)->willReturn($evenement);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Vous n'êtes pas propriétaire de cet événement");
        $this->service->verifierAccesProprietaireEvenement(1);
    }

    public function testRecupererEvenementAvecDettesSucess()
    {
        $user1 = $this->createConfiguredMock(Utilisateur::class, ['getLogin' => 'alice']);
        $user2 = $this->createConfiguredMock(Utilisateur::class, ['getLogin' => 'bob']);
        
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('getMembres')->willReturn([$user1, $user2]);
        
        $depense = $this->createMock(Depense::class);
        $depense->method('getMontant')->willReturn(100.0);
        $depense->method('getPayeur')->willReturn($user1);
        $depense->method('getParticipants')->willReturn([$user1, $user2]);
        
        $this->evenementRepositoryMock->method('recupererParCodeSecret')->willReturn($evenement);
        $this->depenseRepositoryMock->method('recupererParEvenement')->willReturn([$depense]);
        
        $resultat = $this->service->recupererEvenementAvecDettes('ABC123');
        
        $this->assertIsArray($resultat);
        $this->assertArrayHasKey('evenement', $resultat);
        $this->assertArrayHasKey('dettes', $resultat);
        $this->assertArrayHasKey('transactionsOptimisees', $resultat);
        $this->assertArrayHasKey('coutTotal', $resultat);
        $this->assertEquals(100.0, $resultat['coutTotal']);
    }

    public function testRecupererEvenementAvecDettesInexistant(): void
    {
        $this->evenementRepositoryMock->method('recupererParCodeSecret')->willReturn(null);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Événement inexistant");
        
        $this->service->recupererEvenementAvecDettes('INVALID');
    }

    public function testRecupererEvenementsUtilisateur(): void
    {
        $evenements = [
            $this->createMock(Evenement::class),
            $this->createMock(Evenement::class)
        ];
        
        $this->evenementRepositoryMock->method('recupererEvenementsUtilisateur')->willReturn($evenements);
        
        $resultat = $this->service->recupererEvenementsUtilisateur('alice');
        
        $this->assertEquals($evenements, $resultat);
    }

    public function testCreerEvenementAvecSucces(): void
    {
        $utilisateur = $this->createMock(Utilisateur::class);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        $this->evenementRepositoryMock->method('getNextId')->willReturn(1);
        $this->evenementRepositoryMock->method('ajouter')->willReturn(true);
        $this->evenementRepositoryMock->method('ajouterJointure')->willReturn(true);
        
        $codeSecret = $this->service->creerEvenement("Nouvel événement", "alice");
        
        $this->assertNotEmpty($codeSecret);
        $this->assertEquals(64, strlen($codeSecret)); // SHA-256 produces 64 char hex string
    }

    public function testCreerEvenementNomVide(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le nom de l'événement est manquant.");
        
        $this->service->creerEvenement("", "alice");
    }

    public function testCreerEvenementNomTropLong(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du nom de l'événement n'est pas valide.");
        
        $this->service->creerEvenement(str_repeat('a', 31), "alice");
    }

    public function testCreerEvenementEchecAjout(): void
    {
        $utilisateur = $this->createMock(Utilisateur::class);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        $this->evenementRepositoryMock->method('getNextId')->willReturn(1);
        $this->evenementRepositoryMock->method('ajouter')->willReturn(false);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Une erreur est survenue lors de la création de l'événement");
        
        $this->service->creerEvenement("Nouvel événement", "alice");
    }

    public function testCreerEvenementEchecAjoutJointure(): void
    {
        $utilisateur = $this->createMock(Utilisateur::class);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        $this->evenementRepositoryMock->method('getNextId')->willReturn(1);
        $this->evenementRepositoryMock->method('ajouter')->willReturn(true);
        $this->evenementRepositoryMock->method('ajouterJointure')->willReturn(false);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Une erreur est survenue lors de la création de l'événement");
        
        $this->service->creerEvenement("Nouvel événement", "alice");
    }

    public function testMettreAJourEvenementAvecSucces(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->expects($this->once())->method('setTitre')->with('Nouveau nom');
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $codeSecret = $this->service->mettreAJourEvenement(1, 'Nouveau nom');
        
        $this->assertEquals('ABC123', $codeSecret);
    }

    public function testMettreAJourEvenementNomVide(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le nom de l'événement est manquant.");
        
        $this->service->mettreAJourEvenement(1, '');
    }

    public function testMettreAJourEvenementNomTropLong(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du nom de l'événement n'est pas valide.");
        
        $this->service->mettreAJourEvenement(1, str_repeat('a', 31));
    }

    public function testSupprimerEvenementAvecSucces(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estProprietaire')->willReturn(true);
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $this->evenementRepositoryMock->method('supprimer')->willReturn(true);
        
        $this->service->supprimerEvenement(1);
        
        $this->assertTrue(true);
    }

    public function testSupprimerEvenementEchec(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estProprietaire')->willReturn(true);
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $this->evenementRepositoryMock->method('supprimer')->willReturn(false);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Une erreur est survenue lors de la suppression de l'événement.");
        
        $this->service->supprimerEvenement(1);
    }
    
    private function preparerEvenementAvecAccesProprietaire(): Evenement
    {
        // Permet de facilement passer l'étape verifierAccesProprietaireEvenement()
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estProprietaire')->with(ConnexionUtilisateur::getLoginUtilisateurConnecte())->willReturn(true);
        $evenement->method('getId')->willReturn(1);
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->with(1)->willReturn($evenement);
        return $evenement;
    }
    
    public function testRecupererUtilisateursPourAjoutAvecSucces(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('getCodeSecret')->willReturn('ABC123');

        $user1 = $this->createConfiguredMock(Utilisateur::class, ['getLogin' => 'alice']);
        $user2 = $this->createConfiguredMock(Utilisateur::class, ['getLogin' => 'bob']);
        $user3 = $this->createConfiguredMock(Utilisateur::class, ['getLogin' => 'charlie']);
        $utilisateurs = [$user1, $user2, $user3];
        $this->utilisateurRepositoryMock->method('recupererUtilisateursOrdonnesPrenomNom')->willReturn($utilisateurs);
        
        // Simuler que alice et bob sont déjà membres, mais pas charlie
        $evenement->method('estMembre')->willReturnMap([
            ['alice', true],
            ['bob', true],
            ['charlie', false]
        ]);
        
        $resultat = $this->service->recupererUtilisateursPourAjout(1);
        
        $this->assertEquals($evenement, $resultat['evenement']);
        $this->assertCount(1, $resultat['utilisateurs']);
        $this->assertContains($user3, $resultat['utilisateurs']);
    }

    public function testRecupererUtilisateursPourAjoutAucunUtilisateurDisponible(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $user1 = $this->createConfiguredMock(Utilisateur::class, ['getLogin' => 'alice']);
        $this->utilisateurRepositoryMock->method('recupererUtilisateursOrdonnesPrenomNom')->willReturn([$user1]);
        
        // Tous les utilisateurs sont déjà membres
        $evenement->method('estMembre')->willReturn(true);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Aucun utilisateur disponible à ajouter.");

        $this->service->recupererUtilisateursPourAjout(1);
    }

    public function testAjouterMembreAvecSucces(): void
    {
        $loginMembre = "charlie";
        
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('estMembre')->with($loginMembre)->willReturn(false);
        $evenement->method('getCodeSecret')->willReturn('ABC123');

        $utilisateur = $this->createMock(Utilisateur::class);
        
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        $this->evenementRepositoryMock->expects($this->once())->method('ajouterJointure');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');

        $resultat = $this->service->ajouterMembre(1, $loginMembre);

        $this->assertEquals('ABC123', $resultat);
    }

    public function testAjouterMembreLoginUtilisateurNull(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Login du membre à ajouter manquant");
        $this->service->ajouterMembre(1, "");
    }
    
    public function testAjouterMembreUtilisateurInexistant(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('getCodeSecret')->willReturn('ABC123');

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Utilisateur inexistant");
        $this->service->ajouterMembre(1, "bob");
    }

    public function testAjouterMembreDejaMembreDeEvenement(): void
    {
        $loginMembre = "bob";
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('estMembre')->with($loginMembre)->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('ABC123');

        $utilisateur = $this->createMock(Utilisateur::class);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Cet utilisateur est déjà membre de l'événement.");
        $this->service->ajouterMembre(1, $loginMembre);
    }

    public function testQuitterEvenementAvecSucces(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('estProprietaire')->willReturn(false);
        $evenement->method('getId')->willReturn(1);
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $this->depenseRepositoryMock->method('recupererParEvenement')->willReturn(null);
        $this->evenementRepositoryMock->expects($this->once())->method('supprimerJointure');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $this->service->quitterEvenement(1);
        
        $this->assertTrue(true);
    }

    public function testQuitterEvenementEtantProprietaire(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('estProprietaire')->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le propriétaire ne peut pas quitter l'événement.");
        
        $this->service->quitterEvenement(1);
    }

    public function testQuitterEvenementNonMembre(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(false);
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Vous n'avez pas de droits d'éditions sur cet événement");
        
        $this->service->quitterEvenement(1);
    }

    public function testQuitterEvenementAvecDepensesPayees(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('estProprietaire')->willReturn(false);
        $evenement->method('getId')->willReturn(1);
        
        $payeur = $this->createMock(Utilisateur::class);
        $payeur->method('getLogin')->willReturn('alice');
        
        $depense = $this->createMock(Depense::class);
        $depense->method('getPayeur')->willReturn($payeur);
        $depense->method('getId')->willReturn(1);
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $this->depenseRepositoryMock->method('recupererParEvenement')->willReturn([$depense]);
        $this->depenseRepositoryMock->expects($this->once())->method('supprimer')->with(1);
        $this->evenementRepositoryMock->expects($this->once())->method('supprimerJointure');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $this->service->quitterEvenement(1);
        
        $this->assertTrue(true);
    }

    public function testSupprimerMembreUtilisateurInexistant(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Utilisateur inexistant.");
        
        $this->service->supprimerMembre(1, "bob");
    }

    public function testSupprimerMembreUtilisateurNonMembre(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('estMembre')->willReturn(false);
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $utilisateur = $this->createMock(Utilisateur::class);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Cet utilisateur n'est pas membre de l'événement.");
        
        $this->service->supprimerMembre(1, "bob");
    }

    public function testSupprimerMembreUtilisateurEstProprietaire(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $utilisateur = $this->createMock(Utilisateur::class);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        $this->depenseRepositoryMock->method('recupererParEvenement')->willReturn(null);
        $this->evenementRepositoryMock->expects($this->once())->method('supprimerJointure');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $resultat = $this->service->supprimerMembre(1, "bob");
        $this->assertEquals('ABC123', $resultat);
    }

    public function testSupprimerMembreAvecDepensesPayeur(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $utilisateur = $this->createMock(Utilisateur::class);
        $utilisateur->method('getLogin')->willReturn('bob');
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        
        $payeur = $this->createMock(Utilisateur::class);
        $payeur->method('getLogin')->willReturn('bob');
        
        $depense = $this->createMock(Depense::class);
        $depense->method('getPayeur')->willReturn($payeur);
        $depense->method('getId')->willReturn(1);
        
        $this->depenseRepositoryMock->method('recupererParEvenement')->willReturn([$depense]);
        $this->depenseRepositoryMock->expects($this->once())->method('supprimer')->with(1);
        $this->evenementRepositoryMock->expects($this->once())->method('supprimerJointure');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $resultat = $this->service->supprimerMembre(1, "bob");
        $this->assertEquals('ABC123', $resultat);
    }

    public function testSupprimerMembreAvecDepensesDernierParticipant(): void
    {
        /** @var MockObject&Evenement $evenement */
        $evenement = $this->preparerEvenementAvecAccesProprietaire();
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('getCodeSecret')->willReturn('ABC123');
        
        $utilisateur = $this->createMock(Utilisateur::class);
        $utilisateur->method('getLogin')->willReturn('bob');
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        
        $payeur = $this->createMock(Utilisateur::class);
        $payeur->method('getLogin')->willReturn('alice');
        
        $depense = $this->createMock(Depense::class);
        $depense->method('getPayeur')->willReturn($payeur);
        $depense->method('estParticipant')->with('bob')->willReturn(true);
        $depense->method('getParticipants')->willReturn([$utilisateur]);
        $depense->method('getId')->willReturn(1);
        
        $this->depenseRepositoryMock->method('recupererParEvenement')->willReturn([$depense]);
        $this->depenseRepositoryMock->expects($this->once())->method('supprimerJointure')->with($depense, 'bob');
        $this->depenseRepositoryMock->expects($this->once())->method('supprimer')->with(1);
        $this->evenementRepositoryMock->expects($this->once())->method('supprimerJointure');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $resultat = $this->service->supprimerMembre(1, "bob");
        $this->assertEquals('ABC123', $resultat);
    }

    public function testQuitterEvenementAvecDepensesDernierParticipant(): void
    {
        $evenement = $this->createMock(Evenement::class);
        $evenement->method('estMembre')->willReturn(true);
        $evenement->method('estProprietaire')->willReturn(false);
        $evenement->method('getId')->willReturn(1);
        
        $payeur = $this->createMock(Utilisateur::class);
        $payeur->method('getLogin')->willReturn('bob');
        
        $participant = $this->createMock(Utilisateur::class);
        $participant->method('getLogin')->willReturn('alice');
        
        $depense = $this->createMock(Depense::class);
        $depense->method('getPayeur')->willReturn($payeur);
        $depense->method('estParticipant')->with('alice')->willReturn(true);
        $depense->method('getParticipants')->willReturn([$participant]); // seul participant
        $depense->method('getId')->willReturn(1);
        
        $this->evenementRepositoryMock->method('recupererParClePrimaire')->willReturn($evenement);
        $this->depenseRepositoryMock->method('recupererParEvenement')->willReturn([$depense]);
        $this->depenseRepositoryMock->expects($this->once())->method('supprimerJointure')->with($depense, 'alice');
        $this->depenseRepositoryMock->expects($this->once())->method('supprimer')->with(1);
        $this->evenementRepositoryMock->expects($this->once())->method('supprimerJointure');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $this->service->quitterEvenement(1);
        
        $this->assertTrue(true);
    }

}