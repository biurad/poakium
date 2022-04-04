<?php

declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * PHP version 7.4 and above required
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 */

namespace Biurad\Security;

use Biurad\Http\Request;
use Biurad\Security\Handler\RememberMeHandler;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class Helper
{
    private static PropertyAccessorInterface $propertyAccessor;

    /**
     * Determine the targeted url from request, session or referer header.
     */
    public static function determineTargetUrl(
        ServerRequestInterface $request,
        SessionInterface $session = null,
        string $parameter = '_target_path',
        bool $fromReferer = false
    ): ?string {
        if ($targetUrl = self::getParameterValue($request, $parameter)) {
            goto targetUrl;
        }

        if (null === $session && ($request instanceof Request && $request->getRequest()->hasSession())) {
            $session = $request->getRequest()->getSession();
        }

        if (null !== $session && $targetUrl = $session->get($parameter)) {
            $session->remove($parameter);
        }

        targetUrl:
        if ($fromReferer && $targetUrl = ($targetUrl ?? $request->getHeaderLine('Referer'))) {
            if (false !== $pos = \strpos($targetUrl, '?')) {
                $targetUrl = \substr($targetUrl, 0, $pos);
            }

            return $targetUrl;
        }

        return $targetUrl ?: null;
    }

    /**
     * Returns a request "parameter" value.
     *
     * Paths like foo[bar] will be evaluated to find deeper items in nested data structures.
     *
     * @param ServerRequestInterface|\stdClass $data
     *
     * @throws \InvalidArgumentException when the given path is malformed
     *
     * @return mixed
     */
    public static function getParameterValue(object $data, string $path, PropertyAccessorInterface $propertyAccessor = null)
    {
        if ($data instanceof ServerRequestInterface) {
            $getter = static function (string $value) use ($data) {
                if ($data instanceof Request) {
                    $requestedValue = $data->getRequest()->get($value);
                } else {
                    $requestedValue = $data->getAttributes()[$value] ?? $data->getQueryParams()[$value] ?? null;
                }

                if (null === $requestedValue) {
                    $data = (array) ($data->getParsedBody() ?? \json_decode(((string) $data->getBody()) ?: '', true));
                    $requestedValue = $data[$value] ?? null;
                }

                return $requestedValue;
            };

            if (false === $pos = \strpos($path, '[')) {
                return $getter($path);
            }

            if (null === $data = $getter(\substr($path, 0, $pos))) {
                return null;
            }
            $path = \substr($path, $pos);
        }

        try {
            self::$propertyAccessor ??= $propertyAccessor ?? PropertyAccess::createPropertyAccessor();

            return self::$propertyAccessor->getValue($data, $path);
        } catch (AccessException $e) {
            return null;
        }
    }

    /**
     * Fetch the form data from the request.
     *
     * @param array<int,string> $parameterKeys
     *
     * @return array<int,mixed>
     */
    public static function getParameterValues(ServerRequestInterface $request, array $parameterKeys, PropertyAccessorInterface $propertyAccessor = null): array
    {
        if (empty($parameterKeys)) {
            return [];
        }

        foreach ($parameterKeys as $offset => $key) {
            unset($parameterKeys[$offset]);
            $parameterKeys[$key] = self::getParameterValue($request, $key, $propertyAccessor);
        }

        return $parameterKeys;
    }

    /**
     * Creates remember me cookies from token.
     *
     * @return array<int,Cookie>
     */
    public static function createRememberMeCookie(?TokenInterface $fromToken, ServerRequestInterface $request): array
    {
        if (null === $fromToken || !$fromToken->hasAttribute($cookieR = RememberMeHandler::REMEMBER_ME)) {
            return [];
        }

        $cookieId = RememberMeHandler::USERS_ID;
        $cookieUserId = \rawurldecode($request->getCookieParams()[$cookieId] ?? '');

        if (!\str_contains($cookieUserId, $userId = $fromToken->getUserIdentifier())) {
            $cookieUserId = empty($cookieUserId) ? $userId : $cookieUserId . '|' . $userId;
        }

        if (!\is_array($tokenCookies = $fromToken->getAttribute($cookieR))) {
            $tokenCookies = [$tokenCookies];
        }

        $tokenCookies[] = new Cookie($cookieId, $cookieUserId, $tokenCookies[0]->getExpiresTime());

        return $tokenCookies;
    }
}
