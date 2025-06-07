<?php /** @var string $messageErreur */
/** @var int $statusCode */?>
<div class="hero-body pt-0 mt-0">
    <div class="container">
        <div class="columns is-centered">
            <div class="column is-6 box has-text-centered has-text-white has-background-danger">
                <p class="is-size-2">Une erreur est survenue</p>
                <p class="is-size-4">Code d'erreur : <?= htmlspecialchars($statusCode) ?></p>
                <p class="is-size-4">Message : <?= htmlspecialchars($messageErreur) ?></p>
            </div>
        </div>
    </div>
</div>