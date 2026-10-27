<?php

namespace App\Service;

use App\Service\Api\DockerClient;
use GuzzleHttp\Exception\GuzzleException;

class DashboardService
{
    public const string RUNNING_STATE = 'running';
    public const array STOPPED_STATES = ['exited', 'created', 'dead'];

    public const SYSTEM_IMAGE_SUFIX = 'wecker';

    /** @var DockerClient $dockerClient */
    private DockerClient $dockerClient;

    public function __construct(DockerClient $dockerClient)
    {
        $this->dockerClient = $dockerClient;
    }

    /**
     * @return array{running: int, stopped: int, total: int}
     * @throws GuzzleException
     */
    public function getDockerStats(): array
    {
        return $this->processStats($this->dockerClient->getContainers() ?? []);
    }

    /**
     * @return array{data: list<array<string, mixed>>}
     * @throws GuzzleException
     */
    public function getDockerContainerData(): array
    {
        return $this->processContainers($this->dockerClient->getContainers() ?? []);
    }

    /**
     * @param string $id
     * @return array{status: int, message: string}
     * @throws GuzzleException
     */
    public function startContainer(string $id): array
    {
        $statusCode = $this->dockerClient->startContainer($id);

        $message = match ($statusCode) {
            204 => 'Container started',
            404 => 'Container not found',
            304 => 'Container already started',
            default => 'Internal docker api error',
        };

        return [
            'status' => $statusCode == 204 ? 200 : $statusCode,
            'message' => $message,
        ];
    }

    /**
     * @param string $id
     * @return array{status: int, message: string}
     * @throws GuzzleException
     */
    public function stopContainer(string $id): array
    {
        $statusCode = $this->dockerClient->stopContainer($id);

        $message = match ($statusCode) {
            204 => 'Container stopped',
            404 => 'Container not found',
            304 => 'Container already stopped',
            default => 'Internal docker api error',
        };

        return [
            'status' => $statusCode == 204 ? 200 : $statusCode,
            'message' => $message,
        ];
    }

    /**
     * @param string $id
     * @return array{status: int, message: string}
     * @throws GuzzleException
     */
    public function logs(string $id): array
    {
        $logReturn = $this->dockerClient->logs($id);

        return [
            'status' => $logReturn['statusCode'],
            'message' => $logReturn['body'],
        ];
    }

    /**
     * @param list<object> $containers
     * @return array{data: list<array<string, mixed>>}
     */
    private function processContainers(array $containers): array
    {
        $data = [];

        foreach ($containers as $container) {
            if (str_contains($container->Image, self::SYSTEM_IMAGE_SUFIX)) {
                continue;
            }

            $data[] = [
                'id' => $container->Id,
                'name' => $container->Names[0],
                'status' => $container->State,
                'image' => $container->Image,
                'ports' => $this->formatPorts($container->Ports ?? []),
            ];
        }

        return [
            'data' => $data,
        ];
    }

    /**
     * Published ports as "host:container", e.g. "8081:80, 8443:443"; null when none is published.
     * Docker lists each mapping once per IP (0.0.0.0 and ::), hence the de-duplication.
     *
     * @param list<object> $ports
     * @return string|null
     */
    private function formatPorts(array $ports): ?string
    {
        $mappings = [];

        foreach ($ports as $port) {
            if (isset($port->PublicPort)) {
                $mappings[] = $port->PublicPort . ':' . $port->PrivatePort;
            }
        }

        if ($mappings === []) {
            return null;
        }

        return implode(', ', array_unique($mappings));
    }

    /**
     * @param list<object> $containers
     * @return array{running: int, stopped: int, total: int}
     */
    private function processStats(array $containers): array
    {
        $running = 0;
        $stopped = 0;
        $total = count($containers);

        foreach ($containers as $container) {
            if (str_contains($container->Image, self::SYSTEM_IMAGE_SUFIX)) {
                $total--;
                continue;
            }

            if ($container->State === self::RUNNING_STATE) {
                $running++;
            }

            if (in_array($container->State, self::STOPPED_STATES, true)) {
                $stopped++;
            }
        }

        return [
            'running' => $running,
            'stopped' => $stopped,
            'total' => $total,
        ];
    }
}
