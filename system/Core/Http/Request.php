<?php

namespace MADEV\Core\Http;

/**
 * Cette classe représente une requête HTTP.
 *
 * @package MADEV\Core\Http
 * @author  Kevin Ramarozatovo <kevinramarozatovo@gmail.com>
 */
class Request
{
    public $get;
    public $post;
    public $cookies;
    public $files;
    public $server;

    private function __construct(
        $get,
        $post,
        $cookies,
        $files,
        $server
    )
    {
        $this->get     = $get;
        $this->post    = $post;
        $this->cookies = $cookies;
        $this->files   = $files;
        $this->server  = $server;
    }

    /**
     * Crée une instance de la classe Request à partir des variables superglobales de PHP.
     *
     * @return Request
     */
    public static function createFromSuperGlobals()
    {
        return new Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    /**
     * Récupère le paramètre d'URL de la requête, ou utilise "/" par défaut s'il n'est pas présent.
     *
     * @return string L'URL nettoyée sans espaces indésirables au début ou à la fin.
     */
    public function getUrl()
    {
        return trim(isset($this->get['url']) ? $this->get['url'] : '/');
    }

    /**
     * Récupère la méthode de requête HTTP utilisée pour la demande actuelle.
     *
     * @return string La méthode de requête HTTP (par exemple, "GET", "POST", etc.).
     */
    public function getMethod()
    {
        return $this->server['REQUEST_METHOD'];
    }
}
