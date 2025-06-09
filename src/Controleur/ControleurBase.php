<?php

namespace App\VeryBadSplit\Controleur;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ControleurBase extends ControleurGenerique
{

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }


    #[Route(path: '/', name: 'accueil', methods: ['GET'])]
    public function accueil(): Response
    {
        return $this->afficherTwig('base/accueil.html.twig');
    }
}