<?php

/** @var Utilisateur $utilisateur */

use App\VeryBadSplit\Lib\Helper;
use App\VeryBadSplit\Modele\DataObject\Utilisateur;

$loginHTML = htmlspecialchars($utilisateur->getLogin());
$prenomHTML = htmlspecialchars($utilisateur->getPrenom());
$nomHTML = htmlspecialchars($utilisateur->getNom());
$emailHTML = htmlspecialchars($utilisateur->getEmail());
$passwordHTML = htmlspecialchars($utilisateur->getMdp());
?>

<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-8 box has-background-black-ter">
                <p class="is-size-2 has-text-centered has-text-white">Mise à jour du profil</p>
                <form action="<?= Helper::url("compte/modifier") ?>" method="post">
                    <div class="field">
                        <label class="label is-size-4" for="mdpActuel">Afin de confirmer votre identité, saissisez votre mot de passe actuel</label>
                        <div class="control has-icons-left">
                            <input id="mdpActuel" name="mdpActuel" class="input is-large" type="password" placeholder="********"
                                   value="<?=$passwordHTML?>" required>
                            <span class="icon is-small is-left"><ion-icon name="key"></ion-icon></span>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <label class="label is-size-4" for="login">Nom d'utilisateur</label>
                                <div class="control has-icons-left">
                                    <input id="login" name="login" class="input is-large has-background-black" type="text"
                                           placeholder="rlebreton" minlength="3" maxlength="30" value="<?=$loginHTML?>" readonly>
                                    <span class="icon is-left"><ion-icon name="person"></ion-icon></span>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="email">Email</label>
                                <div class="control has-icons-left">
                                    <input id="email" name="email" class="input is-large" type="email"
                                           placeholder="rlebreton@yopmail.com" maxlength="255" value="<?=$emailHTML?>" required>
                                    <span class="icon is-small is-left"><ion-icon name="mail"></ion-icon></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <label class="label is-size-4" for="prenom">Prénom</label>
                                <div class="control has-icons-left">
                                    <input id="prenom" name="prenom" class="input is-large" type="text" placeholder="Romain"
                                           minlength="1" maxlength="30" value="<?=$prenomHTML?>" required>
                                    <span class="icon is-left"><ion-icon name="document"></ion-icon></span>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="nom">Nom</label>
                                <div class="control has-icons-left">
                                    <input id="nom" name="nom" class="input is-large" type="text" placeholder="Lebreton"
                                           minlength="1" maxlength="30" value="<?=$nomHTML?>" required>
                                    <span class="icon is-left"><ion-icon name="document"></ion-icon></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-body">
                            <div class="field">
                                <label class="label is-size-4" for="mdp">Nouveau mot de passe</label>
                                <div class="control has-icons-left">
                                    <input id="mdp" name="mdp" class="input is-large" type="password" placeholder="********"
                                           minlength="6" maxlength="50"
                                           title="6 à 50 caractères, au moins une minuscule, une majuscule et un caractère spécial"
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*_=+\-]).{6,50}">
                                    <span class="icon is-small is-left"><ion-icon name="key"></ion-icon></span>
                                </div>
                            </div>
                            <div class="field">
                                <label class="label is-size-4" for="mdp2">Vérification du mot de passe</label>
                                <div class="control has-icons-left">
                                    <input id="mdp2" name="mdp2" class="input is-large" type="password"
                                           placeholder="********" minlength="6" maxlength="50"
                                           pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*_=+\-]).{6,50}">
                                    <span class="icon is-small is-left"><ion-icon name="key"></ion-icon></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="buttons is-centered">
                        <button class="button has-background-black is-size-4">
                            <span class="icon is-left"><ion-icon name="pencil"></ion-icon></span>
                            <span>Sauvegarder</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
