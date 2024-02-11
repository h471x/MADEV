<?php

namespace MADEV\Core\Routing;

use Exception;
use InvalidArgumentException;
use MADEV\Core\Http\Request;
use MADEV\Core\Http\Response;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;

/**
 * Le Routeur permet de trouver la route qui correspond à une requête HTTP
 * parmi toutes les routes de l'application.
 *
 * @package MADEV\Core\Routing
 * @author  Kevin Ramarozatovo <kevinramarozatovo@gmail.com>
 */
class Router
{
    private $routes = [];

    public function __construct()
    {
        try {
            $this->loadRoutes();
        } catch (Exception $e) {
            echo 'Exception lors de l\'instanciation du Router : ' . $e->getMessage();
            die();
        }
    }

    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Charge les routes à partir du fichier de configuration des routes.
     *
     * @return void
     * @throws Exception
     */
    private function loadRoutes()
    {
        $routesFilePath = CONFIG_PATH . 'routes.json';
        if (!file_exists($routesFilePath))
            throw new InvalidArgumentException("Le fichier de configuration des routes \"$routesFilePath\" est introuvable");

        $rawRoutes = json_decode(file_get_contents($routesFilePath), true, 4);
        self::parseRawRoutes($rawRoutes);

        // Ajout des routes
        foreach ($rawRoutes as $rawRoute)
            $this->addRoute(
                $rawRoute['path'],
                $rawRoute['method'],
                [$rawRoute['controller'], $rawRoute['action']],
                $rawRoute['parameters']
            );
    }

    /**
     * Analyse les données de routes entrées par les utilisateurs
     * dans le fichier de configuration dédié.
     *
     * @param  array $rawRoutes
     * @return void
     * @throws Exception
     */
    private static function parseRawRoutes($rawRoutes)
    {
        if ($rawRoutes === null)
            throw new InvalidArgumentException('Format invalide des routes dans le fichier de configuration. Suivez et respectez la structure de données déjà établie');

        $expectedRoutesKeys = ['path', 'controller', 'action', 'method', 'parameters'];
        foreach ($rawRoutes as $index => $element) {
            if (!is_int($index)) throw new InvalidArgumentException("Indice de route innatendu : \"$index\"");

            // Les clés de $element
            $actualKeys = array_keys($element);

            // Vérification des clés manquantes
            $missingKeys = array_diff($expectedRoutesKeys, $actualKeys);
            if (!empty($missingKeys))
                throw new Exception("Clés manquantes pour la route numéro \"$index\" : \"" . implode(', ', $missingKeys) . "\"");

            // Vérification des clés inattendues
            $unexpectedKeys = array_diff($actualKeys, $expectedRoutesKeys);
            if (!empty($unexpectedKeys))
                throw new Exception("Clés innattendues pour la route numéro \"$index\" : \"" . implode(', ', $unexpectedKeys) . "\"");
        }
    }

    /**
     * Ajoute une nouvelle route dans la liste des routes.
     *
     * @param  string         $path
     * @param  string         $method
     * @param  array|callable $callable
     * @param  array          $parameters
     * @return void
     * @throws Exception Si la route à ajouter existe déjà dans la liste des routes.
     */
    public function addRoute($path, $method, $callable, $parameters = [])
    {
        $newRoute = new Route($path, $method, $callable, $parameters);
        foreach ($this->routes as $route)
            if ($route->equalsTo($newRoute))
                throw new Exception("La route que vous essayez d'ajouter (Path: $path, Method: $method) existe déjà dans la liste des routes");

        $this->routes[] = $newRoute;
    }

    /**
     * Recherche de la route correspondant à l'URL fournie dans la requête.
     *
     * @param  Request $request
     * @return Route|null
     */
    private function findRoute($request)
    {
        /*
         * Splitting de l'url demandé par l'utilisateur.
         *
         * On le divise en segments en utilisant le caractère "/" comme séparateur.
         * Le trim est utilisé pour supprimer les éventuels "/" au début et à la fin de l'URL.
         * Par exemple si l'URL est "/", le trim garantit que $explodedUrl contient un tableau vide.
         *
         * Exemple :
         * - URL fournie : "/path/to/resource/"
         * - Après trim : "path/to/resource"
         * - Après explode : ["path", "to", "resource"]
         */
        $explodedUrl = explode(
            "/",
            trim($request->getUrl(), "/")
        );
        $countExplodedUrl = count($explodedUrl);

        foreach ($this->routes as $route) {
            // Splitting du chemin de la route, similaire à la division de l'URL
            $explodedPath = explode(
                "/",
                trim($route->getPath(), "/")
            );

            if ($countExplodedUrl !== count($explodedPath)) continue;

            for ($i = 0; $i < $countExplodedUrl; $i++) {

                // Si le chemin est dynamique
                if (Route::isParameter($explodedPath[$i]))      $route->addDynamicParameter($explodedPath[$i], $explodedUrl[$i]);
                // Sinon, vérifiez simplement si les segments correspondent
                elseif ($explodedUrl[$i] !== $explodedPath[$i]) break;
            }

            // Si on a parcouru tous les segments avec succès, c'est la route recherchée
            if ($i === $countExplodedUrl) return $route;
        }
        return null;
    }

    /**
     * Dispatche la requête vers la route appropriée et appelle la fonction associée.
     *
     * @param  Request $request    La requête entrante.
     * @return Response
     * @throws ReflectionException Si une erreur survient lors de la réflexion.
     */
    public function dispatch($request)
    {
        $matchedRoute = $this->findRoute($request);
        if ($matchedRoute === null) {
            echo 'Not found';
            die();
        }

        $callable = $matchedRoute->getCallable();
        $parameters =  array_merge($matchedRoute->getParameters(), $matchedRoute->getDynamicParameters());

        $reflectionMethod       = is_array($callable) ? new ReflectionMethod(...$callable) : new ReflectionFunction($callable);
        $functionParameterNames = array_map(function($parameter) {
            return $parameter->getName();
        }, $reflectionMethod->getParameters());

        // Vérification de la différence entre les noms de paramètres de la fonction et de la route
        $parametersKeys = array_keys($parameters);
        if (count($parametersKeys) !== count($functionParameterNames))
            throw new InvalidArgumentException(
                "Le nombre de paramètres de la fonction \"$reflectionMethod->name\" ne correspond pas au nombre de paramètres de la route"
            );

        $difference = array_diff($functionParameterNames, $parametersKeys);
        if (!empty($difference))
            throw new InvalidArgumentException(
                "Les paramètres de la fonction \"$reflectionMethod->name\" ne correspondent pas aux paramètres de la route. " .
                'Différence : "' . implode(', ', $difference) . '"'
            );

        return new Response(
            call_user_func_array($matchedRoute->getCallable(), $parameters),
            200
        );
    }
}
