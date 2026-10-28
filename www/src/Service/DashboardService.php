<?php

namespace App\Service;

use App\Exception\ValidationException;
use App\Service\Api\DockerClient;
use App\Service\Container\Validator\CreateContainerValidator;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class DashboardService
{
    public const string RUNNING_STATE = 'running';
    public const array STOPPED_STATES = ['exited', 'created', 'dead'];

    public const SYSTEM_IMAGE_SUFIX = 'wecker';

    /** @var DockerClient $dockerClient */
    private DockerClient $dockerClient;

    /** @var CreateContainerValidator $createValidator */
    private CreateContainerValidator $createValidator;

    public function __construct(DockerClient $dockerClient, CreateContainerValidator $createValidator)
    {
        $this->dockerClient = $dockerClient;
        $this->createValidator = $createValidator;
    }

    /**
     * Pulls the image when it is not local, creates the container and optionally starts it.
     *
     * @param array<string, mixed> $form the raw form: image, name, ports ("8081:80, 8443:443"),
     *                                   env (one KEY=value per line), restart, start
     * @return array{status: int, message: string}
     * @throws ValidationException
     * @throws GuzzleException
     */
    public function createContainer(array $form): array
    {
        $data = [
            'image' => trim((string) ($form['image'] ?? '')),
            'name' => trim((string) ($form['name'] ?? '')),
            'ports' => $this->splitList((string) ($form['ports'] ?? ''), ','),
            'env' => $this->splitList((string) ($form['env'] ?? ''), "\n"),
            'restart' => (string) ($form['restart'] ?? 'no'),
        ];

        $this->createValidator->validate($data);

        if (!$this->dockerClient->imageExists($data['image'])) {
            $this->dockerClient->pullImage($data['image']);
        }

        try {
            $id = $this->dockerClient->createContainer($this->buildConfig($data), $data['name'] ?: null);
        } catch (ClientException $e) {
            if ($e->getResponse()->getStatusCode() === 409) {
                throw new ValidationException(['name' => 'Container name already in use']);
            }

            throw $e;
        }

        if (!empty($form['start'])) {
            $this->dockerClient->startContainer($id);
        }

        return ['status' => 200, 'message' => 'Container created'];
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
     * @param string $text
     * @param string $separator
     * @return list<string> the non-empty, trimmed items
     */
    private function splitList(string $text, string $separator): array
    {
        $items = [];

        foreach (explode($separator, $text) as $item) {
            $item = trim($item);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Docker "create container" body: "8081:80" becomes ExposedPorts["80/tcp"] + PortBindings["80/tcp"].
     *
     * @param array{image: string, name: string, ports: list<string>, env: list<string>, restart: string} $data
     * @return array<string, mixed>
     */
    private function buildConfig(array $data): array
    {
        $exposedPorts = [];
        $portBindings = [];

        foreach ($data['ports'] as $mapping) {
            [$hostPort, $containerPort] = explode(':', $mapping);
            $exposedPorts["$containerPort/tcp"] = new \stdClass();
            $portBindings["$containerPort/tcp"] = [['HostPort' => $hostPort]];
        }

        $config = [
            'Image' => $data['image'],
            'Env' => $data['env'],
            'HostConfig' => [
                'RestartPolicy' => ['Name' => $data['restart']],
            ],
        ];

        if ($exposedPorts !== []) {
            $config['ExposedPorts'] = $exposedPorts;
            $config['HostConfig']['PortBindings'] = $portBindings;
        }

        return $config;
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
