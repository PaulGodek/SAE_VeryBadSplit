<?php

use App\VeryBadSplit\Lib\Conteneur;
use App\VeryBadSplit\Modele\DataObject\Evenement;
/** @var Evenement $evenement */
/** @var array $utilisateurs */
?>

<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-6 box has-background-black-ter">
                <p class="is-size-2 has-text-centered has-text-white">Ajout d'un membre à l'événement</p>
                <form action="<?= Conteneur::recupererService("generateurUrl")->generate("evenements/ajouterMembre/" . rawurlencode($evenement->getId())) ?>" method="post">
                    <div class="field">
                        <label class="label is-size-4" for="login">Utilisateur à ajouter</label>
                        <div class="select is-size-4 is-fullwidth">
                            <select required id="login" name="login">
                                <?php foreach($utilisateurs as $utilisateur) {?>
                                    <option value="<?=htmlspecialchars($utilisateur->getLogin())?>"><?=$utilisateur->getPrenom()." ".htmlspecialchars($utilisateur->getNom()." (".$utilisateur->getLogin().")")?></option>
                                <?php }?>
                            </select>
                        </div>
                    </div>
                    <div class="buttons is-centered">
                        <button class="button has-background-black is-size-4">
                            <span class="icon is-left"><ion-icon name="person-add"></ion-icon></span>
                            <span>Ajouter à l'événement</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>