<?php

namespace App\VeryBadSplit\Lib;

use App\VeryBadSplit\Modele\HTTP\Session;

class MessageFlash
{

    private static string $cleFlash = "_messagesFlash";

    public static function ajouter(string $type, string $message): void
    {
        $session = Session::getInstance();

        $messagesFlash = [];
        if ($session->contient(MessageFlash::$cleFlash))
            $messagesFlash = $session->lire(MessageFlash::$cleFlash);

        $messagesFlash[$type][] = $message;
        $session->enregistrer(MessageFlash::$cleFlash, $messagesFlash);
    }

    public static function contientMessage(string $type): bool
    {
        $session = Session::getInstance();
        return $session->contient(MessageFlash::$cleFlash) &&
            array_key_exists($type, $session->lire(MessageFlash::$cleFlash))  &&
            !empty($session->lire(MessageFlash::$cleFlash)[$type]);
    }

    public static function lireMessages(string $type): array
    {
        $session = Session::getInstance();
        if (!MessageFlash::contientMessage($type))
            return [];

        $messagesFlash = $session->lire(MessageFlash::$cleFlash);
        $messages = $messagesFlash[$type];
        unset($messagesFlash[$type]);
        $session->enregistrer(MessageFlash::$cleFlash, $messagesFlash);

        return $messages;
    }

    public static function lireTousMessages() : array
    {
        $tousMessages = [];
        foreach(["success", "info", "warning", "danger"] as $type) {
            $tousMessages[$type] = MessageFlash::lireMessages($type);
        }
        return $tousMessages;
    }

}