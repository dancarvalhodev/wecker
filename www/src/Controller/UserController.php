<?php

namespace App\Controller;

use App\Entity\User;
use App\Model\User\UserModel;
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
    /** @var UserModel $userModel */
    private UserModel $userModel;

    public function __construct(Twig $twig, UserModel $userModel)
    {
        parent::__construct($twig);
        $this->userModel = $userModel;
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
            $result = $this->userModel->createUser($data);

            if ($result['success']) {
                return $this->returnWithJson($response->withStatus(201), [
                    'success' => true,
                    'messages' => null
                ]);
            }

            return $this->returnWithJson($response->withStatus(422), [
                'success' => false,
                'messages' => $result['messages']
            ]);
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