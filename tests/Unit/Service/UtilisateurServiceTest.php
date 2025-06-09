<?php

namespace Tests\Unit\Service;

use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
use App\VeryBadSplit\Modele\Repository\Interface\DepenseRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\EvenementRepositoryInterface;
use App\VeryBadSplit\Modele\Repository\Interface\UtilisateurRepositoryInterface;
use App\VeryBadSplit\Service\Exception\ServiceException;
use App\VeryBadSplit\Service\UtilisateurService;
use App\VeryBadSplit\Lib\MotDePasse;
use Tests\Unit\TestCase;

class UtilisateurServiceTest extends TestCase
{
    private UtilisateurService $service;
    private $utilisateurRepositoryMock;
    private $evenementRepositoryMock;
    private $depenseRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->utilisateurRepositoryMock = $this->createMock(UtilisateurRepositoryInterface::class);
        $this->evenementRepositoryMock = $this->createMock(EvenementRepositoryInterface::class);
        $this->depenseRepositoryMock = $this->createMock(DepenseRepositoryInterface::class);
        
        $this->service = new UtilisateurService(
            $this->utilisateurRepositoryMock,
            $this->evenementRepositoryMock,
            $this->depenseRepositoryMock
        );
    }

    public function testRecupererUtilisateurConnecte(): void
    {
        $this->connecterUtilisateur('alice');
        $utilisateur = $this->createMock(Utilisateur::class);
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);

        $result = $this->service->recupererUtilisateurConnecte();

        $this->assertSame($utilisateur, $result);
    }

    public function testRecupererUtilisateurConnecteEtantIntrouvable(): void
    {
        $this->connecterUtilisateur('alice');
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Utilisateur introuvable");
        $this->service->recupererUtilisateurConnecte();
    }
    
    public function testCreerUtilisateurTestComplet(): void
    {
        $login = "alice";
        $utilisateurRepositoryMock = $this->utilisateurRepositoryMock;
        
        $utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);
        $utilisateurRepositoryMock->method('recupererParEmail')->willReturn(null);
        $utilisateurRepositoryMock->method('ajouter')->willReturn(true);
        
        $this->service->creerUtilisateur($login, "Alice", "Martin", "alice@test.com", "Password123!", "Password123!");
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    public function testCreerUtilisateurAtrributsManquants(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Login, nom, prénom, email ou mot de passe manquant.");
        $this->service->creerUtilisateur("", "", "", "", "", "");
    }

    public function testCreerUtilisateurMotsDePasseDifferents(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Mots de passe distincts");

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->service->creerUtilisateur("david", "David", "Jones", "david@test.com", "Password123!", "AutrePassword123!");
    }

    public function testCreerUtilisateurEmailInvalide(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Email non valide");

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->service->creerUtilisateur("charlie", "Charlie", "Brown", "pas-un-email", "Password123!", "Password123!");
    }
    
    public function testCreerUtilisateuLoginTropCourt(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du nom d'utilisateur n'est pas valide.");
        $this->service->creerUtilisateur('al', "Alice", "Martin", "alice@test.com", "Password123!", "Password123!");
    }

    public function testCreerUtilisateuNomTropLong(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du nom n'est pas valide.");
        $this->service->creerUtilisateur('alice', "Alice", str_repeat('A',31), "alice@test.com", "Password123!", "Password123!");
    }

    public function testCreerUtilisateuPrenomTropLong(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du prenom n'est pas valide.");
        $this->service->creerUtilisateur('alice', str_repeat('A',31), "Martin", "alice@test.com", "Password123!", "Password123!");
    }

    public function testCreerUtilisateuMdpInvalide(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le mot de passe ne respecte pas le modèle donné.");
        $this->service->creerUtilisateur('alice', "Alice", "Martin", "alice@test.com", "P", "P");
    }

    public function testCreerUtilisateurLoginExistant(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le login est déjà pris");
        
        $utilisateurExistant = new Utilisateur("bob", "Bob", "Dupont", "bob@test.com", "hash");
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateurExistant);
        
        $this->service->creerUtilisateur("bob", "Robert", "Martin", "autre@test.com", "Password123!", "Password123!");
    }

    public function testCreerUtilisateurEmailExistant(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Ce mail est déjà pris");
        
        $utilisateurExistant = new Utilisateur("alice", "Alice", "Martin", "alice@test.com", "hash");
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);
        $this->utilisateurRepositoryMock->method('recupererParEmail')->willReturn($utilisateurExistant);
        
        $this->service->creerUtilisateur("bob", "Robert", "Martin", "alice@test.com", "Password123!", "Password123!");
    }

    // Modifie le mot de passe
    public function testMettreAJourUtilisateurAvecSucces(): void
    {
        $this->connecterUtilisateur('grace');
        $ancienMdp = "OldPassword123!";
        $utilisateur = new Utilisateur("grace", "Grace", "Hopper", "grace@test.com", MotDePasse::hacher($ancienMdp));

        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        $this->utilisateurRepositoryMock->expects($this->once())->method('mettreAJour');

        $this->service->mettreAJourUtilisateur("grace", "Grace", "Hopper", "grace@new.com", $ancienMdp, "NewPassword123!", "NewPassword123!");

        // Vérifie que le mot de passe a été changé
        $this->assertNotEquals($ancienMdp, $utilisateur->getMdpHache());
    }

    public function testMettreAJourUtilisateurAttributsManquants(): void
    {
        $this->connecterUtilisateur('alice');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Login, nom, prénom, email ou mot de passe actuel manquant.');
        $this->service->mettreAJourUtilisateur('', '', '', '', '', '', '');
    }

    public function testMettreAJourUtilisateurFormatMailInvalide(): void
    {
        $this->connecterUtilisateur('grace');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Email non valide.');
        $this->service->mettreAJourUtilisateur("grace", "Grace", "Hopper", "grace.mail", 'Password123!', "NewPassword123!", "NewPassword123!");
    }

    public function testMettreAJourUtilisateurLoginTropCourt(): void
    {
        $this->connecterUtilisateur('grace');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du nom d'utilisateur n'est pas valide.");
        $this->service->mettreAJourUtilisateur("gr", "Grace", "Hopper", "grace@mail.com", 'Password123!', "NewPassword123!", "NewPassword123!");
    }

    public function testMettreAJourUtilisateurNomTropLong(): void
    {
        $this->connecterUtilisateur('grace');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du nom n'est pas valide.");
        $this->service->mettreAJourUtilisateur("grace", "Grace", str_repeat('G',31), "grace@mail.com", 'Password123!', "NewPassword123!", "NewPassword123!");
    }

    public function testMettreAJourUtilisateurPrenomTropLong(): void
    {
        $this->connecterUtilisateur('grace');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("La longueur du prenom n'est pas valide.");
        $this->service->mettreAJourUtilisateur("grace", str_repeat('G',31), "Hopper", "grace@mail.com", 'Password123!', "NewPassword123!", "NewPassword123!");
    }

    public function testMettreAJourUtilisateurMdpFormatInvalide(): void
    {
        $this->connecterUtilisateur('grace');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le mot de passe ne respecte pas le modèle donné.");
        $this->service->mettreAJourUtilisateur("grace", "Grace", "Hopper", "grace@mail.com", 'Password123!', "NewPassword123", "NewPassword123");
    }

    public function testMettreAJourUtilisateurUtilisateurInexistant(): void
    {
        $this->connecterUtilisateur('grace');
        
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("L'utilisateur n'existe pas.");
        $this->service->mettreAJourUtilisateur("grace", "Grace", "Hopper", "grace@mail.com", 'Password123!', "NewPassword123!", "NewPassword123!");
    }

    public function testMettreAJourUtilisateurMauvaisMotDePasseActuel(): void
    {
        $this->connecterUtilisateur('grace');
        
        $utilisateur = new Utilisateur("grace", "Grace", "Hopper", "grace@test.com", MotDePasse::hacher("BonMotDePasse123!"));
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Le mot de passe actuel entré incorrect");
        $this->service->mettreAJourUtilisateur("grace", "Grace", "Hopper", "grace@mail.com", 'MauvaisMotDePasse123!', "NewPassword123!", "NewPassword123!");
    }

    public function testMettreAJourUtilisateurNouveauMotDePasseManquant(): void
    {
        $this->connecterUtilisateur('grace');

        $utilisateur = $this->createMock(Utilisateur::class);
        $utilisateur->method('getMdpHache')->willReturn(MotDePasse::hacher('Password123!'));
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Pour modifier votre mot de passe, vous devez saisir les 2 champs correspondants.");
        $this->service->mettreAJourUtilisateur("grace", "Grace", "Hopper", "grace@mail.com", 'Password123!', "NewPassword123!", "");
    }

    public function testMettreAJourUtilisateurMotsDePasseDistincts(): void
    {
        $this->connecterUtilisateur('grace');

        $utilisateur = $this->createMock(Utilisateur::class);
        $utilisateur->method('getMdpHache')->willReturn(MotDePasse::hacher('Password123!'));
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Mots de passe distincts.");
        $this->service->mettreAJourUtilisateur("grace", "Grace", "Hopper", "grace@mail.com", 'Password123!', "NewPassword123!", "DifferentPassword123!");
    }

    // Supprime l'utilisateur avec des évènements
    public function testSupprimerUtilisateurAvecSucces(): void
    {
        $this->connecterUtilisateur('henry');

        $membre = $this->createConfiguredMock(Utilisateur::class, ['getLogin' => 'paul',]);

        $evenement = $this->createMock(Evenement::class);
        $this->evenementRepositoryMock->method('recupererEvenementsUtilisateur')->willReturn([$evenement]);
        $evenement->method('getMembres')->willReturn([$membre]);
        $evenement->expects($this->once())->method('setMembres');
        $this->evenementRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $depenses = [
            $this->createConfiguredMock(Depense::class, ['estPayeur' => true, 'getId' => 1]),
            $this->createConfiguredMock(Depense::class, [
                'estPayeur' => false,
                'estParticipant' => true,
                'getParticipants' => [],
            ]),
            $this->createConfiguredMock(Depense::class, [
                'estPayeur' => false,
                'estParticipant' => true,
                'getParticipants' => [$membre],
            ]),
        ];
        
        $this->depenseRepositoryMock->method('recupererDepensesPayeesOuParticipeUtilisateur')->willReturn($depenses);
        $this->depenseRepositoryMock->expects($this->once())->method('supprimer')->with(1);
        
        $this->utilisateurRepositoryMock->expects($this->once())->method('supprimer')->willReturn(true);
        $this->service->supprimerUtilisateur("henry");

        // Vérifie que l'utilisateur n'est plus connecté
        $this->assertArrayNotHasKey('_utilisateurConnecte', $_SESSION);
    }

    public function testConnecterUtilisateurAvecSucces(): void
    {
        $login = "alice";
        $password = "Password123!";
        $utilisateur = new Utilisateur($login, "Alice", "Martin", "alice@test.com", MotDePasse::hacher($password));
        
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        
        $this->service->connecterUtilisateur($login, $password);
        
        $this->assertEquals($login, $_SESSION['_utilisateurConnecte']);
    }

    public function testConnecterUtilisateurAttributManquant(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Login ou mot de passe manquant.');
        $this->service->connecterUtilisateur('', '');
    }

    public function testConnecterUtilisateurUtilisateurInexistant(): void
    {
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Login inconnu.');
        $this->service->connecterUtilisateur('a', 'a');
    }

    public function testConnecterUtilisateurMauvaisMotDePasse(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Mot de passe incorrect");
        
        $utilisateur = new Utilisateur("eve", "Eve", "Wilson", "eve@test.com", MotDePasse::hacher("BonMotDePasse"));
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn($utilisateur);
        
        $this->service->connecterUtilisateur("eve", "MauvaisMotDePasse");
    }
    
    public function testRecupererUtilisateurParEmail(): void
    {
        $email = "test@example.com";
        $utilisateur = new Utilisateur("user1", "User", "One", $email, "hash1");
        
        $this->utilisateurRepositoryMock->method('recupererParEmail')->with($email)->willReturn($utilisateur);
        
        $result = $this->service->recupererUtilisateurParEmail($email);
        
        $this->assertSame($utilisateur, $result);
    }

    public function testRecupererUtilisateurParEmailMailVide(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Adresse email manquante.");
        $this->service->recupererUtilisateurParEmail('');
    }

    public function testRecupererUtilisateurParEmailAucunUtilisateur(): void
    {
        $this->utilisateurRepositoryMock->method('recupererParEmail')->willReturn(null);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Aucun compte associé à cette adresse email.");
        $this->service->recupererUtilisateurParEmail('a@test.com');
    }
    
    public function testVerifierConnexionDeconnecte(): void
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("Vous devez être connecté pour cela.");
        $this->service->verifierConnexion();
    }

    public function testReinitialiserMotDePasseAvecSucces(): void
    {
        $login = "alice";
        $nouveauMdp = "NewPassword123!";
        $utilisateur = $this->createMock(Utilisateur::class);
        
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with($login)->willReturn($utilisateur);
        $utilisateur->expects($this->once())->method('setMdpHache');
        $this->utilisateurRepositoryMock->expects($this->once())->method('mettreAJour');
        
        $this->service->reinitialiserMotDePasse($login, $nouveauMdp);
    }

    public function testReinitialiserMotDePasseUtilisateurInexistant(): void
    {
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->willReturn(null);
        
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage("L'utilisateur n'existe pas.");
        
        $this->service->reinitialiserMotDePasse("inexistant", "Password123!");
    }

    public function testRecupererUtilisateurParClePrimaire(): void
    {
        $this->connecterUtilisateur('alice');
        $utilisateur = $this->createMock(Utilisateur::class);
        
        $this->utilisateurRepositoryMock->method('recupererParClePrimaire')->with('alice')->willReturn($utilisateur);
        
        $resultat = $this->service->recupererUtilisateurParClePrimaire('alice');
        
        $this->assertSame($utilisateur, $resultat);
    }
}