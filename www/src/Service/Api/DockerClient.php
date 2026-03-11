<?php

namespace App\Service\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class DockerClient
{
    private Client $client;

    private const SOCKET = '/var/run/docker.sock';
    private const VERSION = 'v1.43';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost/' . self::VERSION . '/',
            'curl' => [
                CURLOPT_UNIX_SOCKET_PATH => self::SOCKET
            ]
        ]);
    }

    /**
     * @throws GuzzleException
     */
    public function ping(): bool
    {
        $response = $this->client->get('_ping');

        return trim((string)$response->getBody()) === 'OK';
    }
}