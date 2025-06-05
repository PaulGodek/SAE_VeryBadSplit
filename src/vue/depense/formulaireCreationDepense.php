<?php
use App\VeryBadSplit\Modele\DataObject\Evenement;
/** @var Evenement $evenement */
?>

<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-8 box has-background-black-ter">
                <p class="is-size-2 has-text-centered has-text-white">Ajout d'une dépense</p>
                <form action="<?= \App\VeryBadSplit\Lib\Helper::url("evenements/nouvelleDepense/" . htmlspecialchars($evenement->getId())) ?>" method="post">
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <label class="label is-size-4" for="titre">Titre de la dépense</label>
                                <div class="control has-icons-left">
                                    <input id="titre" name="titre" class="input is-large" type="text"
                                           placeholder="Ma super dépense" minlength="1" maxlength="50" required>
                                    <span class="icon is-left"><ion-icon name="newspaper"></ion-icon></span>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="montant">Montant (minimum 1€)</label>
                                <div class="control has-icons-left">
                                    <input id="montant" name="montant" class="input is-large" type="number" step="0.1" min="1" max="10000" value="1.0" required>
                                    <span class="icon is-small is-left"><ion-icon name="card"></ion-icon></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <label class="label is-size-4" for="payeur">Payé par</label>
                                <div class="select is-size-4 is-fullwidth">
                                    <select required id="payeur" name="payeur">
                                        <option value="" disabled selected hidden></option>
                                        <?php foreach ($evenement->getMembres() as $membre) {?>
                                            <option value="<?=htmlspecialchars($membre->getLogin())?>"><?=htmlspecialchars($membre->getPrenom()." ".$membre->getNom()." (".$membre->getLogin().")")?></option>
                                        <?php }?>

                                    </select>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="participants">Participants</label>
                                <div class="select is-multiple is-size-4 is-fullwidth">
                                    <select required id="participants" name="participants[]" multiple size="<?= min(count($evenement->getMembres()), 4) ?>">
                                        <?php foreach ($evenement->getMembres() as $membre) {?>
                                            <option value="<?=htmlspecialchars($membre->getLogin())?>"><?=htmlspecialchars($membre->getPrenom()." ".$membre->getNom()." (".$membre->getLogin().")")?></option>
                                        <?php }?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="buttons is-centered mt-6">
                        <button class="button has-background-black is-size-4">
                            <span class="icon is-left"><ion-icon name="add-circle"></ion-icon></span>
                            <span>Enregister la dépense</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
