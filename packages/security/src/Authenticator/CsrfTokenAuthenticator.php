<?php

namespace Biurad\Security\Authenticator;

use Biurad\Security\Interfaces\AuthenticatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Authenticates whether the given CSRF token is valid.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class CsrfTokenAuthenticator implements AuthenticatorInterface
{
    private CsrfTokenManagerInterface $csrfTokenManager;
    private string $csrfTokenId;
    private string $csrfParameter;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager, string $csrfTokenId = 'authenticate', string $csrfParameter = '_csrf_token')
    {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->csrfTokenId = $csrfTokenId;
        $this->csrfParameter = $csrfParameter;
    }

    /**
     * {@inheritdoc}
     */
    public function setToken(?TokenInterface $token): void
    {
        // This authenticator does not use a token.
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ServerRequestInterface $request): bool
    {
        return 'POST' === $request->getMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(ServerRequestInterface $request, array $credentials): ?TokenInterface
    {
        if (empty($csrfToken = $credentials[$this->csrfParameter] ?? null)) {
            return null;
        }

        if (!\is_string($csrfToken)) {
            throw new BadRequestException(\sprintf('The key "%s" must be a string, "%s" given.', $this->csrfParameter, \gettype($csrfToken)));
        }

        $csrfToken = new CsrfToken($this->csrfTokenId, $csrfToken);

        if (false === $this->csrfTokenManager->isTokenValid($csrfToken)) {
            throw new InvalidCsrfTokenException('Invalid CSRF token.');
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function failure(ServerRequestInterface $request, AuthenticationException $exception): ?ResponseInterface
    {
        return null;
    }
}
