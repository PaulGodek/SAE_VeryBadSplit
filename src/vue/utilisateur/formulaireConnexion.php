<?php

use App\VeryBadSplit\Modele\HTTP\Cookie;

?>
<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-6 box has-background-black-ter">
                <p class="is-size-2 has-text-centered has-text-white-ter">Connexion</p>
                <form action="controleurFrontal.php" method="post">
                    <div class="field">
                        <label class="label is-size-4" for="login">Nom d'utilisateur</label>
                        <div class="control has-icons-left">
                            <input id="login" name="login" class="input is-large" type="text"
                                   placeholder="rlebreton"
                                   value="<?= Cookie::contient("login") ? Cookie::lire("login") : "" ?>" required>
                            <span class="icon is-left"><ion-icon name="person"></ion-icon></span>
                        </div>
                    </div>
                    <div class="field">
                        <label class="label is-size-4" for="password">Mot de passe</label>
                        <div class="control has-icons-left">
                            <input id="password" name="mdp" class="input is-large" type="password"
                                   placeholder="********"
                                   value="<?= Cookie::contient("mdp") ? Cookie::lire("mdp") : "" ?>" required>
                            <span class="icon is-small is-left"><ion-icon name="key"></ion-icon></span>
                        </div>
                    </div>
                    <div id="connexionOubli">
                        <a href="controleurFrontal.php?action=afficherFormulaireRecuperationCompte&controleur=utilisateur">Login
                            et/ou mot de passe oubliés ?</a>
                    </div>
                    <input type='hidden' name='action' value='connecter'>
                    <input type='hidden' name='controleur' value='utilisateur'>
                    <div class="buttons is-centered">
                        <button class="button has-background-black is-size-4">
                            <span class="icon is-left"><ion-icon name="log-in"></ion-icon></span>
                            <span>Connexion</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>