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
     * @return list<object>|null
     * @throws GuzzleException
     */
    public function getContainers(): array|null
    {
        $res = $this->client->request('GET', 'containers/json', [
            'query' => [
                'all' => true,
            ],
        ]);

        return json_decode((string) $res->getBody());
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
     * @return array{statusCode: int, body: string}
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

        $body = (string) $res->getBody();

        // Without a TTY, Docker sends stdout/stderr interleaved, each chunk behind an 8-byte header.
        if (str_contains($res->getHeaderLine('Content-Type'), 'multiplexed-stream')) {
            $body = $this->demultiplex($body);
        }

        return [
            'statusCode' => $res->getStatusCode(),
            'body' => $body,
        ];
    }

    /**
     * Each chunk: 1 byte stream type, 3 zero bytes, 4 bytes big-endian payload size, then the payload.
     *
     * @param string $stream
     * @return string
     */
    private function demultiplex(string $stream): string
    {
        $output = '';

        while (strlen($stream) >= 8) {
            $size = unpack('N', substr($stream, 4, 4))[1];
            $output .= substr($stream, 8, $size);
            $stream = substr($stream, 8 + $size);
        }

        return $output;
    }
}
