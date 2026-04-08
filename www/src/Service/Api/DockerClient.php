<?php

namespace App\Service\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;
use Throwable;

class DockerClient
{
    /** @var ?Client */
    private ?Client $client;

    /** @var string */
    private const string VERSION = 'v1.43';

    /** @var ?Client */
    private static ?Client $cached = null;

    /**
     * @return void
     */
    public function __construct()
    {
        if (self::$cached) {
            $this->client = self::$cached;

            return;
        }

        $this->client = self::$cached = $this->detectClient();
    }

    /**
     * @return Client
     */
    private function detectClient(): Client
    {
        $candidates = [
            // TCP (WSL2 / Docker Desktop)
            [
                'base_uri' => 'http://host.docker.internal:2375/' . self::VERSION . '/',
            ],
            [
                'base_uri' => 'http://127.0.0.1:2375/' . self::VERSION . '/',
            ],

            // Socket (Linux)
            [
                'base_uri' => 'http://localhost/' . self::VERSION . '/',
                'curl' => [
                    CURLOPT_UNIX_SOCKET_PATH => '/var/run/docker.sock',
                ],
            ],
        ];

        foreach ($candidates as $config) {
            $config['timeout'] = 5;
            $config['connect_timeout'] = 5;

            $client = new Client($config);

            if ($this->isAlive($client)) {
                return $client;
            }
        }

        throw new RuntimeException('Docker não acessível');
    }

    /**
     * @param Client $client
     * @return bool
     */
    private function isAlive(Client $client): bool
    {
        try {
            $res = $client->get('_ping', ['timeout' => 1]);
            return trim((string)$res->getBody()) === 'OK';
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws GuzzleException
     */
    public function ping(): bool
    {
        $res = $this->client->get('_ping');
        return trim((string)$res->getBody()) === 'OK';
    }
}