<?php

namespace App\VeryBadSplit\Controleur;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Attribute\Route;

class ControleurBase extends ControleurGenerique
{

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    #[Route(path: '/', name: 'accueil', methods: ['GET'])]
    public function accueil(): void
    {
        $this->afficherVue('vueGenerale.php', [
            "pagetitle" => "Accueil",
            "cheminVueBody" => "base/accueil.php"
        ]);
    }
}