<?php

namespace Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Authenticator;

use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTNotFoundEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\UserNotFoundException;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\PassportInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Exception;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Vinatis\Bundle\SecurityLdapBundle\Bridge\Symfony\Security\Exception\UnsupportedMediaTypeException;
use Vinatis\Bundle\SecurityLdapBundle\Manager\UserLdapManager;
use Vinatis\Bundle\SecurityLdapBundle\Model\UserLdapInterface;
use Vinatis\Bundle\SecurityLdapBundle\Service\ActiveDirectory;

final class LdapAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private ActiveDirectory $activeDirectory;
    private JWTEncoderInterface $encoder;
    private EntityManagerInterface $manager;
    private RefreshTokenManagerInterface $refreshTokenManager;
    private UserLdapManager $userLdapManager;

    public function __construct(
        UserLdapManager $userLdapManager,
        ActiveDirectory $activeDirectory,
        JWTEncoderInterface $encoder,
        EntityManagerInterface $manager,
        RefreshTokenManagerInterface $refreshTokenManager
    )
    {
        $this->userLdapManager = $userLdapManager;
        $this->activeDirectory = $activeDirectory;
        $this->encoder = $encoder;
        $this->manager = $manager;
        $this->refreshTokenManager = $refreshTokenManager;
    }

    public function supports(Request $request): ?bool
    {
        return 'authentication_token_backoffice' === $request->attributes->get('_route') &&
            $request->isMethod(Request::METHOD_POST)
            ;
    }

    public function authenticate(Request $request): PassportInterface
    {
        // If header isn't json
        if ('json' != $request->getContentType() || null == $request->getContentType()) {
            throw new UnsupportedMediaTypeException('WRONG CONTENT-TYPE');
        }

        $body = json_decode($request->getContent());
        if (!isset($body->password) || !isset($body->email) || null == $body->email || null == $body->password) {
            throw new BadRequestException('ERROR IN REQUEST');
        }

        $loginFromRequest = $body->email;
        $passwordFromRequest = $body->password;
        $ldapEntry = $this->activeDirectory->getUser($loginFromRequest, $passwordFromRequest);

        if (null == $ldapEntry) {
            throw new UserNotFoundException($loginFromRequest, 'IMPOSSIBLE TO RETRIEVE THE RESOURCE');
        } else {
            $entity = $this->loadUser($loginFromRequest);
            if (!$entity instanceof UserLdapInterface) {
                $entity = $this->userLdapManager->create($ldapEntry, $passwordFromRequest);
                $this->manager->persist($entity);
            } else {
                $this->userLdapManager->update($ldapEntry, $entity, $passwordFromRequest);
            }
            $this->manager->flush();
        }

        // IF EVERYTHING DID WELL THEN RETURN THE PASSPORT (SELF VALIDATED BECAUSE WE'VE ALREADY CHECKED THE USER)
        return new SelfValidatingPassport(
            new UserBadge($loginFromRequest,
                function () use($loginFromRequest) {
                    return $this->loadUser($loginFromRequest);
                })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        try {
            $user = $this->loadUser($token->getUser()->getEmail());
            $jwtToken = $this->encoder->encode([
                'username' => $token->getUser()->getEmail(),
                'roles' => $user->getRoles(),
                'id' => $user->getId(),
                'exp' => time() + 1300
            ]);

            $refreshToken = $this->refreshTokenManager->getLastFromUsername($token->getUser()->getEmail());
            if( null !== $refreshToken) {
                $this->refreshTokenManager->delete($refreshToken);
            }

            //$refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($token->getUser(), 2592000);
            $refreshToken = $this->refreshTokenManager->create();
            $refreshToken->setUsername($user->getEmail());
            $refreshToken->setValid((new \DateTime())->modify('+1 days'));
            $refreshToken->setRefreshToken(null);

            $this->manager->persist($refreshToken);
            $this->manager->flush();
        } catch (JWTEncodeFailureException $JWTEncodeFailureException) {
            return new JsonResponse(['message' => $JWTEncodeFailureException->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse([
            'token' => $jwtToken,
            'provider' => 'ldap',
            'user' => [
                'id' => (string)$user->getId(),
                'email' => $user->getEmail(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'roles' => $user->getRoles(),
            ],
            'refresh_token' => $refreshToken->getRefreshToken(),

        ], Response::HTTP_CREATED);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        /* DYNAMICALLY GENERATES THE CODE RESPONSE */
        if ($exception instanceof UnsupportedMediaTypeException) {
            $codeResponse = Response::HTTP_UNSUPPORTED_MEDIA_TYPE;
        } elseif ($exception instanceof BadRequestException) {
            $codeResponse = Response::HTTP_BAD_REQUEST;
        } elseif ($exception instanceof UserNotFoundException) {
            $codeResponse = Response::HTTP_NOT_FOUND;
        } else {
            $codeResponse = Response::HTTP_UNAUTHORIZED;
        }

        $data = [
            'error' => strtr($exception->getMessageKey(), $exception->getMessageData()),
        ];
        return new JsonResponse($data, $codeResponse);
    }

    public function start(Request $request, AuthenticationException $authException = null): ?Response
    {
        $exception = new MissingTokenException('JWT Token not found', 0, $authException);
        $event = new JWTNotFoundEvent($exception, new JWTAuthenticationFailureResponse($exception->getMessageKey()));
        return $event->getResponse();
    }

    protected function loadUser(string $loginFromRequest): ?UserLdapInterface
    {
        return $this->manager->getRepository($this->userLdapManager->getUserClass())->findOneBy(['email' => $loginFromRequest]);
    }
}