<?php

namespace App\Controller;

use App\Entity\User;
use App\Exception\UserException;
use App\Exception\ValidationException;
use App\Model\User\UserModel;
use App\Service\AuthService;
use App\Service\User\Normalizer\GeneralNormalizer;
use App\Service\User\Validator\LoginValidator;
use App\Service\User\Validator\RegisterValidator;
use Doctrine\DBAL\Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class UserController extends AbstractController
{
    /** @var RegisterValidator $registerValidator */
    private RegisterValidator $registerValidator;

    /** @var GeneralNormalizer $generalNormalizer */
    private GeneralNormalizer $generalNormalizer;

    /** @var LoginValidator $loginValidator */
    private LoginValidator $loginValidator;

    /** @var UserModel $userModel */
    private UserModel $userModel;

    private AuthService $auth;

    /**
     * @param Twig $twig
     * @param UserModel $userModel
     * @param RegisterValidator $registerValidator
     * @param LoginValidator $loginValidator
     * @param GeneralNormalizer $generalNormalizer
     * @param AuthService $auth
     */
    public function __construct(Twig $twig, UserModel $userModel, RegisterValidator $registerValidator, LoginValidator $loginValidator, GeneralNormalizer $generalNormalizer, AuthService $auth)
    {
        parent::__construct($twig);

        $this->registerValidator = $registerValidator;
        $this->generalNormalizer = $generalNormalizer;
        $this->loginValidator = $loginValidator;
        $this->auth = $auth;
        $this->userModel = $userModel;
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ValidationException
     */
    public function register(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {

            $data = $request->getParsedBody();

            if (!is_array($data)) {
                throw new ValidationException(['Invalid request payload.']);
            }

            $data = $this->generalNormalizer->clean($data);
            $this->registerValidator->validate($data);
            $user = $this->userModel->createUser($data);
            $this->auth->login($user);

            return $this->returnWithSuccess($response);
        }

        return $this->getTwig()->render($response, 'crud/user/register.html.twig');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws ValidationException
     * @throws UserException
     */
    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->auth->isAuthenticated()) {
            return $response->withHeader('Location', '/dashboard')->withStatus(302);
        }

        if ($request->getMethod() === 'POST') {
            $data = $request->getParsedBody();

            if (!is_array($data)) {
                throw new ValidationException(['Invalid request payload.']);
            }

            $data = $this->generalNormalizer->clean($data);
            $this->loginValidator->validate($data);
            $user = $this->userModel->authenticateUser($data);
            $this->auth->login($user);

            return $this->returnWithSuccess($response);
        }

        return $this->getTwig()->render($response, 'crud/user/login.html.twig');
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->auth->isAuthenticated()) {
            $this->auth->logout();
        }

        return $response->withHeader('Location', '/')->withStatus(302);
    }
}