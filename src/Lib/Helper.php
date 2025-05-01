<?php

namespace App\VeryBadSplit\Lib;

class Helper
{

    /**
     * Génère une URL absolue à partir d'un chemin relatif.
     *
     * @param string $path Le chemin relatif.
     * @return string L'URL absolue générée.
     */
    public static function url(string $path): string {
        $assistantUrl = \App\VeryBadSplit\Lib\Conteneur::recupererService("assistantUrl");
        return $assistantUrl->getAbsoluteUrl($path);
    }
}