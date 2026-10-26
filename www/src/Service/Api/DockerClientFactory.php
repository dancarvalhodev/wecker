<?php

declare(strict_types=1);

namespace App\Service\Api;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use RuntimeException;
use Throwable;

final class DockerClientFactory
{
    /** @var string */
    public const string VERSION = 'v1.54';

    /**
     * Probes the known daemon endpoints in order and returns a client for the first one that answers.
     *
     * @return ClientInterface
     * @throws RuntimeException when no endpoint answers
     */
    public function create(): ClientInterface
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
     * @param ClientInterface $client
     * @return bool
     */
    private function isAlive(ClientInterface $client): bool
    {
        try {
            $res = $client->request('GET', '_ping', ['timeout' => 1]);
            return trim((string) $res->getBody()) === 'OK';
        } catch (Throwable) {
            return false;
        }
    }
}
