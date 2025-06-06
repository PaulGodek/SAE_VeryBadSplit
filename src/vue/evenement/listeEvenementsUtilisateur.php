<?php

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Modele\DataObject\Evenement;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;

/** @var Evenement[] $evenements
 * @var Utilisateur[] $membres*/
?>

<div class="hero-body pt-0 mt-5 is-align-items-stretch">
    <div class="container">
        <div class="has-text-centered is-size-2 has-text-white-ter">
            <p>Mes évenements (<?= count($evenements) ?>)</p>
        </div>
        <div class='columns is-centered mt-2'>
            <div class='column is-6'>
                <div class="has-text-centered">
                    <a class="button has-background-black has-text-white-ter is-size-4" href="<?= Helper::url('evenements/creation'); ?>">
                        <span class="icon is-left"><ion-icon name="add-circle"></ion-icon></span>
                        <span>Créer un événement</span>
                    </a>
                </div>
                <div class="mt-5">
                    <?php foreach ($evenements as $evenement) { ?>
                        <div class="box media has-background-black has-text-white-ter">
                            <div class="media-content">
                                <div class="is-size-3"><?= $evenement->getTitre()?></div>
                                <div>
                                    <div class="has-text-left is-size-5">
                                        <span class="icon"><ion-icon name="time"></ion-icon></span>
                                        <span>Créé le <?=htmlspecialchars($evenement->getDate()->format('d/m/Y'))?></span>
                                    </div>
                                    <div class="has-text-left is-size-5 mt-2">
                                        <span class="icon"><ion-icon name="person"></ion-icon></span>
                                        <?php foreach ($membres as $membre) { ?>
                                            <span class="liste-membre-item"><?=htmlspecialchars($membre->getPrenom()[0].$membre->getNom()[0])?></span>
                                        <?php }?>
                                    </div>
                                </div>
                            </div>
                            <div class="media-right">
                                <div class="buttons">
                                    <a class="icon-container" href="<?= Helper::url('evenements/' . rawurlencode($evenement->getCodeSecret())) ?>">
                                        <span class="icon"><ion-icon name="eye"></ion-icon></span>
                                    </a>
                                    <?php
                                    if ($evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {
                                        ?>
                                        <a class="icon-container" href="<?= Helper::url('evenements/modifier/' . rawurlencode($evenement->getId())) ?>">
                                            <span class="icon"><ion-icon name="pencil"></ion-icon></span>
                                        </a>
                                        <a class="delete" href="<?= Helper::url('evenements/supprimer/' . rawurlencode($evenement->getId())) ?>"></a>
                                    <?php } else { ?>
                                        <a class="icon-container" href="<?= Helper::url('evenements/quitter/' . rawurlencode($evenement->getId())) ?>">
                                            <span class="icon"><ion-icon name="person-remove"></ion-icon></span>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
