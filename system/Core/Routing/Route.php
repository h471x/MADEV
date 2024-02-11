<?php

namespace MADEV\Core\Routing;

use InvalidArgumentException;
use MADEV\Core\Controllers\BaseController;

/**
 * La classe Route représente une route dans une application.
 *
 * Chaque requête HTTP est associée à une et une seule route,
 * et cette route sait quel contrôleur, quelle action appeler et quels paramètres il faut utiliser.
 *
 * @package MADEV\Core\Routing
 * @author  Kevin Ramarozatovo <kevinramarozatovo@gmail.com>
 */
class Route
{
    private $path;              // Le chemin associé à la route.
                                // Il peut contenir des segments dynamiques entre accolades,
                                // et doit commencer par un "/".
    private $method;            // La méthode de la requête
    private $callable;          // L'action à exécuter lorsque la route est atteinte
    private $parameters;        // Les paramètres prévus pour la route
    private $dynamicParameters; // Les paramètres dynamiques extraits du chemins

    /**
     * @param string         $path
     * @param string         $method
     * @param array|callable $callable
     * @param array          $parameters
     */
    function __construct($path, $method, $callable, $parameters = [])
    {
        $this->setPath($path);
        $this->setMethod($method);
        $this->setCallable($callable);
        $this->setParameters($parameters);
        $this->dynamicParameters = [];
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return callable
     */
    public function getCallable()
    {
        return $this->callable;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     */
    public function getDynamicParameters()
    {
        return $this->dynamicParameters;
    }

    /**
     * @param  string $path
     * @return void
     */
    private function setPath($path)
    {
        if (empty($path))      throw new InvalidArgumentException('Le chemin associé à une route ne doit pas être vide');
        if (!is_string($path)) throw new InvalidArgumentException('Le chemin associé à une route doit être une chaîne de caractères');
        if (!string_starts_with($path, "/"))
            throw new InvalidArgumentException('Le chemin associé à une route doit commmencer par un "/"');

        $this->path = trim($path);
    }

    /**
     * @param  string $method
     * @return void
     */
    private function setMethod($method)
    {
        if (empty($method))
            throw new InvalidArgumentException("La méthode HTTP ne peut pas être vide pour la route associée à \"$this->path\"");
        if (!is_string($method))
            throw new InvalidArgumentException("La méthode HTTP fournie doit être une chaîne de caractères pour la route associée à \"$this->path\"");

        $method = strtoupper($method);
        if (!in_array($method, ['GET', 'DELETE', 'POST', 'PUT']))
            throw new InvalidArgumentException("La méthode HTTP fournie n'est pas valide pour la route associée à \"$this->path\"");

        $this->method = $method;
    }

    /**
     * @param  array|callable $callable
     * @return void
     */
    private function setCallable($callable)
    {
        if (empty($callable)) throw new InvalidArgumentException('Le callable ne peut pas être vide');

        if (is_array($callable)) {
            if (count($callable) != 2)
                throw new InvalidArgumentException('Le tableau associé au callable doit avoir exactement 2 éléments : [controller, action]');

            list($controller, $action) = $callable;
            if (!is_string($controller) || !is_string($action))
                throw new InvalidArgumentException('Chaque élément du tableau associé au callable doit être une chaîne de caractères');

            $controller  = trim($controller);
            $action      = trim($action);
            if (empty($controller) || empty($action))
                throw new InvalidArgumentException('Le controller et l\'action associées à la route ne peuvent pas être vides');

            $controller = new $controller;
            if (!is_a($controller, BaseController::class))
                throw new InvalidArgumentException(get_class($controller) . ' n\'est pas un contrôleur');
            if (!method_exists($controller, $action))
                throw new InvalidArgumentException("La méthode \"$action\" n'existe pas dans le contrôleur " . get_class($controller));

            $this->callable = [$controller, $action];
        }
        elseif (is_callable($callable)) $this->callable = $callable;
        else throw new InvalidArgumentException('Le callable associé à une route doit être une fonction valide ou un tableau [controller, action]');
    }

    /**
     * @param  array $parameters
     * @return void
     */
    private function setParameters($parameters)
    {
        if (!is_array($parameters))
            throw new InvalidArgumentException('Les noms de paramètres d\'une route doivent être sous forme de tableau');
        if (get_array_depth($parameters) != 1)
            throw new InvalidArgumentException('Le tableau des noms de paramètres doit avoir une profondeur de 1');
        foreach ($parameters as $param) if (!is_string($param) || is_numeric($param))
                                            throw new InvalidArgumentException('Chaque nom de paramètre doit être un "string"');

        $this->parameters = [];
        foreach ($parameters as $parameter) $this->parameters[$parameter] = null;
        $this->bindParameters();
    }

    /**
     * Lie les valeurs des paramètres de la route aux paramètres superglobaux GET et POST.
     * Les valeurs sont extraites en fonction des noms des paramètres définis dans la route.
     *
     * @return void
     */
    private function bindParameters() {
        switch($this->method) {
            case "GET":
            case "DELETE":
                foreach ($this->parameters as $key => $_) {
                    if (isset($_GET[$key]))
                        $this->parameters[$key] = $_GET[$key];
                }
                break;
            case "POST":
            case "PUT":
                foreach ($this->parameters as $key => $_) {
                    if (isset($_POST[$key]))
                        $this->parameters[$key] = $_POST[$key];
                }
                break;
        }
    }

    /**
     * Vérifie si une partie du chemin dans une route dynamique est un paramètre.
     *
     * Les routes dynamiques sont de la forme : /formations/{slug_formation}.
     * Les paramètres sont inclus entre une paire d'accolades "{}", et il ne doit pas y avoir
     * d'accolades supplémentaires à l'intérieur d'une paire.
     *
     * @param  string $pathPart La partie du chemin à vérifier.
     * @return bool             True si c'est un paramètre, false sinon.
     */
    static function isParameter($pathPart)
    {
        if (
            !string_contains($pathPart, "{") &&
            !string_contains($pathPart, "}")
        ) return false;

        if (!self::hasBalancedBraces($pathPart))
            throw new InvalidArgumentException("Erreur au niveau des accolades : \"$pathPart\"");

        if (substr_count($pathPart, '{') === 1 && substr_count($pathPart, '}') === 1) {
            $insideBraces = substr($pathPart, 1, -1);

            if (is_numeric($insideBraces)) throw new InvalidArgumentException('Le nom d\'un paramètre doit être un "string"');
            if ($insideBraces === '')      throw new InvalidArgumentException('Aucun nom de paramètre trouvé à l\'intérieur des accolades');

            return preg_match('/^\{(.+?)}$/', $pathPart) === 1;
        }
        else throw new InvalidArgumentException("Plus d'une paire d'accolades a été trouvé : \"$pathPart\"");
    }

    /**
     * Un helper qui vérifie l'équilibre des accolades dans une chaîne de caractères.
     *
     * @param  string $string La chaîne de caractères à analyser.
     * @return bool           True si les accolades sont équilibrées, false sinon.
     */
    private static function hasBalancedBraces($string)
    {
        $verif = 0;
        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];

            if ($char === '{')     $verif++;
            elseif ($char === '}') $verif--;

            if ($verif < 0) return false;
        }
        return $verif === 0;
    }

    /**
     * Ajoute un paramètre dynamique à la liste des paramètres dynamiques de la route.
     *
     * @param  string $parameterName Le nom du paramètre dynamique,
     *                               délimité par des accolades, par exemple "{id}".
     * @param  mixed  $value         La valeur à assigner au paramètre.
     * @return void
     */
    function addDynamicParameter($parameterName, $value)
    {
        $this->dynamicParameters[substr($parameterName, 1, -1)] = $value;
    }

    /**
     * Vérifie si cette instance de route est équivalente à une autre route.
     *
     * Deux routes sont considérées équivalentes si elles ont les mêmes valeurs
     * pour le chemin et la méthode HTTP.
     *
     * @param  Route $route
     * @return bool  True si les routes sont équivalentes, sinon false.
     */
    public function equalsTo($route)
    {
        if (!is_a($route, 'MADEV\Core\Routing\Route')) throw new InvalidArgumentException('La variable $route doit être une route');

        if ($this->path === $route->path && $this->method === $route->method) return true;
        else return false;
    }
}
