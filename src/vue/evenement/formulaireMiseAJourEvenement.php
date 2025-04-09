<?php
/** @var Evenement $evenement */

use App\VeryBadSplit\Modele\DataObject\Evenement;

?>
<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-6 box has-background-black-ter">
                <p class="is-size-2 has-text-centered has-text-white">Mise à jour d'un événement</p>
                <form action="controleurFrontal.php" method="post">
                    <div class="field">
                        <label class="label is-size-4" for="nomEvenement">Nom de l'événement</label>
                        <div class="control has-icons-left">
                            <input id="nomEvenement" name="nomEvenement" class="input is-large" type="text"
                                   placeholder="Mon super événement" minlength="3" maxlength="50" value="<?=htmlspecialchars($evenement->getTitre())?>" required>
                            <span class="icon is-left"><ion-icon name="newspaper"></ion-icon></span>
                        </div>
                    </div>
                    <input type='hidden' name='idEvenement' value='<?= htmlspecialchars($evenement->getId()) ?>'>
                    <input type='hidden' name='action' value='mettreAJourEvenement'>
                    <input type='hidden' name='controleur' value='evenement'>
                    <div class="buttons is-centered">
                        <button class="button has-background-black is-size-4">
                            <span class="icon is-left"><ion-icon name="pencil"></ion-icon></span>
                            <span>Mettre à jour</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>