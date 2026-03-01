<?php

namespace App\Controller;

use App\Service\User\Normalizer\FormNormalizer;
use App\Service\User\Validator\FormValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserController extends AbstractController
{
    private FormValidator $formValidator;
    private FormNormalizer $formNormalizer;
    public function __construct(Twig $twig, FormValidator $formValidator, FormNormalizer $formNormalizer)
    {
        parent::__construct($twig);
        $this->formValidator = $formValidator;
        $this->formNormalizer = $formNormalizer;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();
            $data = $this->formNormalizer->clean($data);
            $this->formValidator->validate($data);

            return $this->returnWithJson($response, ['name' => 'John Doe']);
        }

        return $this->getTwig()->render($response, 'crud/user/register.html.twig');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            return $this->returnWithJson($response, ['name' => 'John Doe']);
        }

        return $this->getTwig()->render($response, 'crud/user/login.html.twig');
    }
}