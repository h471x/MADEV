<?php

namespace MADEV\Core\Http;

use InvalidArgumentException;

/**
 * Représente la réponse HTTP générée par l'application.
 *
 * @package MADEV\Core\Http
 * @author  Kevin Ramarozatovo <kevinramarozatovo@gmail.com>
 */
class Response
{
    private $content;
    private $statusCode;
    private $headers;

    public function __construct(
        $content    = '',
        $statusCode = 200,
        $headers    = []
    )
    {
        $this->setContent($content);
        $this->setStatusCode($statusCode);
        $this->setHeaders($headers);
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        if (!is_string($content)) throw new InvalidArgumentException('Le contenu d\'une réponse doit être une chaîne de caractères');
        $this->content = $content;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        if (!is_int($statusCode)) throw new InvalidArgumentException('Le "status code" doit être un "integer"');
        $this->statusCode = $statusCode;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        if (!is_array($headers))
            throw new InvalidArgumentException('Les en-têtes doivent être spécifiés sous forme de tableau');
        $this->headers = $headers;
    }

    /**
     * Cette fonction envoie de la réponse HTTP (le code d'état, les en-têtes et le contenu) au client.
     *
     * @return void
     */
    public function send()
    {
        // Définition du code d'état HTTP
        http_response_code($this->statusCode);

        // Définition des en-têtes
        foreach ($this->headers as $header) header($header);

        // Envoi du contenu
        echo $this->content;
    }
}
