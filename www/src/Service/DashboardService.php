<?php

namespace App\Service;

use App\Exception\DockerException;
use App\Service\Api\DockerClient;
use GuzzleHttp\Exception\GuzzleException;
use function PHPUnit\Framework\matches;

class DashboardService
{
    const string RUNNING_STATE = 'running';
    const array STOPPED_STATES = ['exited', 'created', 'dead'];

    const SYSTEM_IMAGE_SUFIX = 'wecker';

    /**
     * @return array
     * @throws DockerException
     * @throws GuzzleException
     */
    public function getDockerStats(): array
    {
        $dockerClient = new DockerClient();
        $containers = $dockerClient->getContainers();

        if (!$containers) {
            throw new DockerException(['Docker containers not found']);
        }

        return $this->processStats($containers);
    }

    /**
     * @return array
     * @throws DockerException
     * @throws GuzzleException
     */
    public function getDockerContainerData(): array
    {
        $dockerClient = new DockerClient();
        $containers = $dockerClient->getContainers();

        if (!$containers) {
            throw new DockerException(['Docker containers not found']);
        }

        return $this->processContainers($containers);
    }

    /**
     * @param string $id
     * @return array
     * @throws GuzzleException
     */
    public function startContainer(string $id): array
    {
        $dockerClient = new DockerClient();
        $statusCode = $dockerClient->startContainer($id);

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
     * @return array
     * @throws GuzzleException
     */
    public function stopContainer(string $id): array
    {
        $dockerClient = new DockerClient();
        $statusCode = $dockerClient->stopContainer($id);

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
     * @return array
     * @throws GuzzleException
     */
    public function logs(string $id): array
    {
        $dockerClient = new DockerClient();
        $logReturn = $dockerClient->logs($id);

        return [
            'status' => $logReturn['statusCode'],
            'message' => $logReturn['body'],
        ];
    }

    /**
     * @param array $containers
     * @return array
     */
    private function processContainers(array $containers): array
    {
        $data = [];

        foreach ($containers as $container) {
            if (str_contains($container->Image, self::SYSTEM_IMAGE_SUFIX)) {
                continue;
            }

            $ports = null;

            if (isset($container->Ports[0]) && isset($container->Ports[1])) {
                $ports = $container->Ports[0]->PublicPort . ':' . $container->Ports[1]->PrivatePort;
            }

            $data[] = [
                'id' => $container->Id,
                'name' => $container->Names[0],
                'status' => $container->State,
                'image' => $container->Image,
                'ports' => $ports,
            ];
        }

        return [
            'data' => $data,
        ];
    }

    /**
     * @param array $containers
     * @return array
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
            'total' => $total
        ];
    }
}