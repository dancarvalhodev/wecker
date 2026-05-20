<?php

namespace App\Controller;

use App\Exception\DockerException;
use App\Exception\ValidationException;
use App\Service\Api\DockerClient;
use App\Service\DashboardService;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class DashboardController extends AbstractController
{
    /** @var DashboardService $dashboardService */
    private $dashboardService;

    public function __construct(Twig $twig, DashboardService $dashboardService)
    {
        parent::__construct($twig);

        $this->dashboardService = new DashboardService();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws DockerException
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->dashboardService->getDockerStats();

        return $this->getTwig()->render($response, 'dashboard/index.html.twig', [
            'docker' => [
                'running' => $data['running'],
                'stopped' => $data['stopped'],
                'total' => $data['total']
            ]
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws DockerException
     * @throws GuzzleException
     */
    public function list(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->dashboardService->getDockerContainerData();

        return $this->returnWithJson($response, $data);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws ValidationException
     * @throws GuzzleException
     */
    public function start(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                throw new ValidationException(['Invalid request payload.']);
            }

            if (!isset($data['id'])) {
                throw new ValidationException(['ID not found.']);
            }

            $dockerResponse = $this->dashboardService->startContainer($data['id']);

            return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws ValidationException
     * @throws GuzzleException
     */
    public function stop(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                throw new ValidationException(['Invalid request payload.']);
            }

            if (!isset($data['id'])) {
                throw new ValidationException(['ID not found.']);
            }

            $dockerResponse = $this->dashboardService->stopContainer($data['id']);

            return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws ValidationException
     */
    public function log(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                throw new ValidationException(['Invalid request payload.']);
            }

            if (!isset($data['id'])) {
                throw new ValidationException(['ID not found.']);
            }

            $dockerResponse = $this->dashboardService->logs($data['id']);

            return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws ValidationException
     */
    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                throw new ValidationException(['Invalid request payload.']);
            }

            $dockerResponse = $this->dashboardService->create($data);

            return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
        }
    }
}