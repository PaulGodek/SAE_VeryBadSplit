<?php

use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;
/** @var Utilisateur $utilisateur */
?>

<div class="hero-body pt-0 mt-5 is-align-items-stretch">
    <div class="container">
        <div class="has-text-centered is-size-2 has-text-white-ter">
            <p><strong>Informations des comptes concernés</strong></p>
        </div>
        <div class="columns is-centered mt-5 has-text-white-ter">
            <div class="column is-4">

                    <div class="box has-background-black">
                        <div class="has-text-centered is-size-3"><span><strong><?=htmlspecialchars($utilisateur->getPrenom()." ".$utilisateur->getNom())?></strong></span></div>
                        <div>
                            <p ><strong>Login : <?=htmlspecialchars($utilisateur->getLogin())?></strong></p>
                            <form action="<?= Helper::url("reinitialisation") ?>" method="post">
                                <div class="field is-horizontal">
                                    <div class="field-body">
                                        <div class="field">
                                            <label class="label is-size-5" for="mdp">Nouveau mot de passe</label>
                                            <div class="control has-icons-left">
                                                <input id="mdp" name="mdp" class="input is-large" type="password" placeholder="********"
                                                       minlength="6" maxlength="50"
                                                       title="6 à 50 caractères, au moins une minuscule, une majuscule et un caractère spécial"
                                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*_=+\-]).{6,50}">
                                                <span class="icon is-small is-left"><ion-icon name="key"></ion-icon></span>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <label class="label is-size-5" for="mdp2">Vérification du mot de passe</label>
                                            <div class="control has-icons-left">
                                                <input id="mdp2" name="mdp2" class="input is-large" type="password"
                                                       placeholder="********" minlength="6" maxlength="50"
                                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*_=+\-]).{6,50}">
                                                <span class="icon is-small is-left"><ion-icon name="key"></ion-icon></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="login" value="<?= htmlspecialchars($utilisateur->getLogin()) ?>">
                                <div class="buttons is-centered">
                                    <button class="button has-background-black is-size-4">
                                        <span class="icon is-left"><ion-icon name="pencil"></ion-icon></span>
                                        <span>Reinitialiser</span>
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>

            </div>
        </div>
    </div>
</div>