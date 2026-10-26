<?php

namespace App\Service\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class DockerClient
{
    /** @var ClientInterface */
    private ClientInterface $client;

    /**
     * @param ClientInterface $client already pointing at the daemon's versioned API (see DockerClientFactory)
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @throws GuzzleException
     */
    public function getContainers(): array|null
    {
        $res = $this->client->request('GET', 'containers/json', [
            'query' => [
                'all' => true,
            ],
        ]);

        return $res->getBody() ? json_decode($res->getBody()->getContents()) : null;
    }

    /**
     * @throws GuzzleException
     */
    public function startContainer(string $id): int
    {
        $res = $this->client->request('POST', "containers/$id/start");

        return $res->getStatusCode();
    }

    /**
     * @throws GuzzleException
     */
    public function stopContainer(string $id): int
    {
        $res = $this->client->request('POST', "containers/$id/stop");

        return $res->getStatusCode();
    }

    /**
     * @throws GuzzleException
     */
    public function logs(string $id): array
    {
        $res = $this->client->request('GET', "containers/$id/logs", [
            'query' => [
                'stdout' => true,
                'stderr' => true,
                'tail' => 10,
            ],
        ]);

        header('Content-Type: text/plain');

        return [
            'statusCode' => $res->getStatusCode(),
            'body' => $res->getBody() ? $res->getBody()->getContents() : null,
        ];
    }
}
