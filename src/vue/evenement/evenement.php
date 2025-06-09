<?php

use App\VeryBadSplit\Lib\ConnexionUtilisateur;
use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;
/** @var Evenement $evenement */
/** @var Depense $depenses */
/** @var int $coutTotal */
/** @var array $dettes */
/** @var array $transactions */

$titreEvenementHTML = htmlspecialchars($evenement->getTitre());
$dateEvenementHTML = htmlspecialchars($evenement->getDate()->format('d/m/Y'));
$coutTotalEvenementHTML = htmlspecialchars(number_format($coutTotal,2));
?>

<div class="hero-body pt-0 mt-5 is-align-items-stretch">
    <div class="container box has-background-black-ter">
        <div class="columns is-centered">
            <div class="column is-10">
                <div class="container">
                    <div class="has-text-white-ter has-text-centered ">
                        <div class="is-size-2">
                            <span><?=$evenement->getTitre()?></span>
                        </div>
                        <div>
                            <span class="icon"><ion-icon name="time"></ion-icon></span>
                            <span>Date : <?=$dateEvenementHTML?></span>
                            <span class="icon"><ion-icon name="card"></ion-icon></span>
                            <span>Coût total : <?=$coutTotalEvenementHTML?>€</span>
                        </div>
                        <div class="buttons is-centered mt-2">
                            <?php if($evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {?>
                                <a class="button is-link" href="<?= Helper::url('evenements/modifier/' . rawurlencode($evenement->getId())) ?>">
                                    <span class="icon is-left"><ion-icon name="pencil"></ion-icon></span>
                                    <span>Editer</span>
                                </a>
                                <?php if($evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {?>
                                    <a class="button is-danger" href="<?= Helper::url('evenements/supprimer/' . rawurlencode($evenement->getId())) ?>">
                                        <span class="icon is-left"><ion-icon name="trash"></ion-icon></span>
                                        <span>Supprimer</span>
                                    </a>
                                <?php } else {?>
                                    <a class="button is-danger" href="<?= Helper::url('evenements/quitter/' . rawurlencode($evenement->getId())) ?>">
                                        <span class="icon is-left"><ion-icon name="person-remove"></ion-icon></span>
                                        <span>Quitter</span>
                                    </a>
                                <?php }?>
                            <?php }?>
                        </div>
                    </div>
                    <div class="columns">
                        <div class="column is-half">
                            <div class="has-text-centered">
                                <p class="is-size-4"><strong>Dépenses</strong></p>
                                <?php if($evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {?>
                                    <a class="button has-background-black has-text-white-ter is-size-4" href="<?= Helper::url("evenements/nouvelleDepense/" . rawurlencode($evenement->getId())) ?>">
                                        <span class="icon is-left"><ion-icon name="add-circle"></ion-icon></span>
                                        <span>Ajouter une dépense</span>
                                    </a>
                                <?php }?>
                            </div>
                            <div class="mt-5 is-overflow-y-auto scrollable-list pr-3">
                                <?php foreach ($depenses as $depense) { ?>
                                    <div class="box media">
                                        <div class="media-content">
                                            <div class="is-size-4">
                                                <span><strong><?=$depense->getTitre()?></strong></span>
                                            </div>
                                            <div class="has-text-left is-size-5">
                                                <span class="icon is-left"><ion-icon name="time"></ion-icon></span>
                                                <span>Le <?=htmlspecialchars($depense->getDate()->format('d/m/Y'))?></span>
                                            </div>
                                            <div class="has-text-left is-size-5">
                                                <span class="icon is-left"><ion-icon name="card"></ion-icon></span>
                                                <span><?=htmlspecialchars(number_format($depense->getMontant(),2))?>€ payé par <?=htmlspecialchars($depense->getPayeur()->getPrenom())." ".$depense->getPayeur()->getNom()?></span>
                                            </div>
                                            <div class="has-text-left is-size-5 mt-2">
                                                <span class="icon is-left"><ion-icon name="person"></ion-icon></span>
                                                <?php foreach ($depense->getParticipants() as $participant) { ?>
                                                    <span class="liste-membre-item"><?=htmlspecialchars($participant->getPrenom()[0].$participant->getNom()[0])?></span>
                                                <?php }?>
                                            </div>
                                        </div>
                                        <div class="media-right">
                                            <?php if($evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())) {?>
                                                <a class="icon-container" href="<?= Helper::url("depense/modifier/" . rawurlencode($depense->getId())); ?>"><span class="icon pb-3"><ion-icon name="pencil"></ion-icon></span></a>
                                                <a class="delete" href="<?= Helper::url("depense/supprimer/" . rawurlencode($depense->getId())); ?>"></a>
                                            <?php }?>
                                        </div>
                                    </div>
                                <?php }?>
                            </div>
                        </div>
                        <div class="column is-half">
                            <div>
                                <div class="has-text-centered">
                                    <p class="is-size-4"><strong>Membres</strong></p>
                                    <?php if($evenement->estMembre(ConnexionUtilisateur::getLoginUtilisateurConnecte())){?>
                                        <?php if($evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte())){?>
                                            <a class="button has-background-black has-text-white-ter is-size-4" href="<?= Helper::url("evenements/ajouterMembre/" . rawurlencode($evenement->getId())) ?>">
                                                <span class="icon is-left"><ion-icon name="person-add"></ion-icon></span>
                                                <span>Ajouter un membre</span>
                                            </a>
                                        <?php } else {?>
                                            <button disabled class="button has-background-black has-text-white-ter is-size-4">
                                                <span class="icon is-left"><ion-icon name="person-add"></ion-icon></span>
                                                <span>Ajouter un membre</span>
                                            </button>
                                        <?php }?>
                                    <?php }?>
                                </div>
                                <div class="mt-5 has-background-black-ter is-overflow-y-auto scrollable-list pr-3">
                                    <?php foreach ($evenement->getMembres() as $membre) { ?>
                                        <div class="box media">
                                            <div class="media-content">
                                                <div class="is-size-4 is-wrapped">
                                                    <span><strong><?= htmlspecialchars($membre->getPrenom() . " " . $membre->getNom()) ?></strong></span>
                                                </div>

                                                <?php foreach ($transactions as $transaction) { ?>
                                                    <?php if ($transaction["from"] === $membre->getLogin()) { ?>
                                                        <div class="has-text-left is-size-5">
                                                            <span class="icon is-left"><ion-icon name="card"></ion-icon></span>
                                                            <span>Doit <?= htmlspecialchars(number_format($transaction["montant"], 2)) ?>€ à <?= htmlspecialchars($evenement->getMembres()[$transaction["to"]]->getPrenom() . " " . $evenement->getMembres()[$transaction["to"]]->getNom()) ?></span>
                                                        </div>
                                                    <?php } ?>
                                                <?php } ?>
                                            </div>
                                            <div class="media-right">
                                                <?php if($evenement->estProprietaire(ConnexionUtilisateur::getLoginUtilisateurConnecte()) && $evenement->getProprietaire()->getLogin() !== $membre->getLogin()){?>
                                                    <a class="delete" href="<?= Helper::url("evenements/supprimerMembre/" . rawurlencode($evenement->getId()) . "/" . rawurlencode($membre->getLogin())) ?>"></a>
                                                <?php }?>
                                            </div>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>