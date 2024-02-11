<?php

namespace MADEV\Core\Controllers;

use InvalidArgumentException;
use MADEV\Core\App;

/**
 * Classe abstraite représentant le contrôleur de base qui
 * sert de classe parente pour tous les contrôleurs de l'application.
 *
 * Elle fournit une structure de base pour les fonctionnalités communes à tous les contrôleurs.
 * Les contrôleurs sont responsables de la gestion des requêtes, de la coordination des actions, et
 * de la communication avec les vues ou les modèles.
 *
 * @package MADEV\Core\Controllers
 * @author  Kevin Ramarozatovo <kevinramarozatovo@gmail.com>
 */
abstract class BaseController
{
    /**
     * Cette fonction sert à retourner une page HTML.
     *
     * @param  string $viewPath La vue à retourner.
     * @param  array  $data     Les données à passer à la vue.
     * @return string
     */
    protected function renderView($viewPath, $data = [])
    {
        if (!isset($data)) $data = [];
        if (!is_array($data))
            throw new InvalidArgumentException('Les données passées aux vues doivent être sous forme de tableau');
        extract($data, EXTR_SKIP);

        // Début de la capture de la sortie
        ob_start();
        // Inclusion du fichier dans la mémoire tampon
        self::loadView($viewPath);
        // Récupération du contenu de la mémoire tampon et vidage de la mémoire tampon
        return ob_get_clean();
    }

    /**
     * Charge une vue dans l'application.
     *
     * @param  string $viewPath Le nom de la vue à charger.
     * @return void
     */
    protected static function loadView($viewPath)
    {
        if (empty($viewPath)) throw new InvalidArgumentException('Le nom de la vue ne doit pas être vide');
        if (!is_string($viewPath) || is_numeric($viewPath)) throw new InvalidArgumentException('Le nom de la vue doit être une chaîne de caractères');

        $viewPath = trim($viewPath);
        $viewFilePath = APP_PATH . "Views/$viewPath";
        // Check si $viewPath n'a pas l'extension ".php". Si non, on la rajoute
        if (empty(pathinfo($viewPath, PATHINFO_EXTENSION))) $viewFilePath .= '.php';

        if (!file_exists($viewFilePath)) throw new InvalidArgumentException("La vue \"$viewFilePath\" n'existe pas");

        include_once $viewFilePath;
    }

    protected function redirectToRoute($routePath)
    {
        header("Location: $routePath");
        die();
    }
}
