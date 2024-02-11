<?php

namespace MADEV\Core;

use Exception;
use InvalidArgumentException;
use MADEV\Core\Http\Request;
use MADEV\Core\Routing\Router;

require_once(SYS_PATH . 'utils/functions.php');

/**
 * Le bootstrap de l'application.
 *
 * @package MADEV\Core
 * @author  Kevin Ramarozatovo <kevinramarozatovo@gmail.com>
 */
class App
{
    private $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Exécute l'application.
     * @return void
     */
    public function run()
    {
        try {
            // Création de la requête
            $request  = Request::createFromSuperGlobals();

            // Traitement de celle-ci
            $response = $this->router->dispatch($request);

            // Envoie de la réponse générée au client
            $response->send();
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
            die();
        }
    }
}
