<?php

namespace App\Controller;

use App\Exception\ValidationException;
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
    private DashboardService $dashboardService;

    public function __construct(Twig $twig, DashboardService $dashboardService)
    {
        parent::__construct($twig);

        $this->dashboardService = $dashboardService;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws GuzzleException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->dashboardService->getDockerStats();

        return $this->getTwig()->render($response, 'dashboard/index.html.twig', [
            'docker' => [
                'running' => $data['running'],
                'stopped' => $data['stopped'],
                'total' => $data['total'],
            ],
        ]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
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
        $dockerResponse = $this->dashboardService->startContainer($this->extractContainerId($request));

        return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
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
        $dockerResponse = $this->dashboardService->stopContainer($this->extractContainerId($request));

        return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
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
        $dockerResponse = $this->dashboardService->logs($this->extractContainerId($request));

        return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
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
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            throw new ValidationException(['Invalid request payload.']);
        }

        $dockerResponse = $this->dashboardService->createContainer($data);

        return $this->returnWithJson($response, [$dockerResponse['message']], $dockerResponse['status']);
    }

    /**
     * The id goes straight into the Docker API URL, so only a real container id (hex) is accepted.
     *
     * @param ServerRequestInterface $request
     * @return string
     * @throws ValidationException
     */
    private function extractContainerId(ServerRequestInterface $request): string
    {
        $data = $request->getParsedBody();

        if (!is_array($data)) {
            throw new ValidationException(['Invalid request payload.']);
        }

        if (!isset($data['id'])) {
            throw new ValidationException(['ID not found.']);
        }

        if (!is_string($data['id']) || !ctype_xdigit($data['id'])) {
            throw new ValidationException(['Invalid container ID.']);
        }

        return $data['id'];
    }
}
