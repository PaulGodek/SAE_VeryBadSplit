<?php
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
/** @var Utilisateur[] $utilisateurs */
?>

<div class="hero-body pt-0 mt-5 is-align-items-stretch">
    <div class="container">
        <div class="has-text-centered is-size-2 has-text-white-ter">
            <p><strong>Informations des comptes concernés</strong></p>
        </div>
        <div class="columns is-centered mt-5 has-text-white-ter">
            <div class="column is-4">
                <?php foreach ($utilisateurs as $utilisateur) { ?>
                    <div class="box has-background-black">
                        <div class="has-text-centered is-size-3"><span><strong><?=htmlspecialchars($utilisateur->getPrenom()." ".$utilisateur->getNom())?></strong></span></div>
                        <div>
                            <p><strong>Login : <?=htmlspecialchars($utilisateur->getLogin())?></strong></p>
                            <p><strong>Mot de passe : <?=htmlspecialchars($utilisateur->getMdp())?></strong></p>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>