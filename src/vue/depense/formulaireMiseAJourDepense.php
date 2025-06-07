<?php

use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Modele\DataObject\Depense;
use App\VeryBadSplit\Modele\DataObject\Evenement;

/** @var Depense $depense
 * @var Evenement $evenement*/
?>

<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-8 box has-background-black-ter">
                <p class="is-size-2 has-text-centered has-text-white">Edition d'une dépense</p>
                <form action="<?= \App\VeryBadSplit\Lib\Helper::url("depense/modifier/" . htmlspecialchars($depense->getId())) ?>" method="post">
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <label class="label is-size-4" for="titre">Titre de la dépense</label>
                                <div class="control has-icons-left">
                                    <input id="titre" name="titre" class="input is-large" type="text"
                                           placeholder="Ma super dépense" minlength="1" maxlength="50" value="<?= $depense->getTitre()?>" required>
                                    <span class="icon is-left"><ion-icon name="newspaper"></ion-icon></span>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="montant">Montant (minimum 1€)</label>
                                <div class="control has-icons-left">
                                    <input id="montant" name="montant" class="input is-large" type="number" step="0.1" min="1" max="10000" value="<?= htmlspecialchars($depense->getMontant())?>" required>
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
                                        <?php foreach ($evenement->getMembres() as $membre) {?>
                                            <option <?= $depense->estPayeur($membre->getLogin()) ? "selected" : ""?> value="<?=htmlspecialchars($membre->getLogin())?>"><?=htmlspecialchars($membre->getPrenom()." ".$membre->getNom()." (".$membre->getLogin().")")?></option>
                                        <?php }?>
                                    </select>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="participants">Participants</label>
                                <div class="box" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($evenement->getMembres() as $membre):
                                        $login = htmlspecialchars($membre->getLogin());
                                        $nomComplet = htmlspecialchars($membre->getPrenom() . " " . $membre->getNom() . " ($login)");
                                        $isParticipant = $depense->estParticipant($membre->getLogin());
                                        $isPayeur = $depense->estPayeur($membre);
                                        ?>
                                        <div class="level mb-2">
                                            <div class="level-left">
                                                <?php if (!$isParticipant): ?>
                                                    <label>
                                                        <input class="mr-2" type="checkbox" name="participants[]" value="<?= $login ?>">
                                                        <span class="is-size-5"><?= $nomComplet ?></span>
                                                    </label>
                                                <?php else: ?>
                                                    <span class="is-size-5"><?= $nomComplet ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($isParticipant): ?>
                                                <div class="level-right">
                                                    <a class="delete is-medium" title="Supprimer"
                                                       href="<?= Helper::url("depense/supprimerParticipant/" . rawurlencode($depense->getId()) . "/" . rawurlencode($login)) ?>">
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="buttons is-centered mt-6">
                        <button class="button has-background-black is-size-4">
                            <span class="icon is-left"><ion-icon name="add-circle"></ion-icon></span>
                            <span>Sauvegarder</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
