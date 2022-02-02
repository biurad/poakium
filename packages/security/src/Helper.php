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
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

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
            if ($data instanceof Request) {
                $getter = [$data->getRequest()->getSession(), 'get'];
            } else {
                $getter = static function (string $value) use ($data) {
                    $data = $data->getAttributes()[$value] ?? $data->getQueryParams()[$value] ?? null;

                    if (null === $data) {
                        if (null === $parsedBody = $data->getParsedBody()) {
                            return null;
                        }

                        if ($parsedBody instanceof \stdClass) {
                            return $parsedBody->{$value} ?? null;
                        }

                        $data = $parsedBody[$value] ?? null;
                    }

                    return $data;
                };
            }

            if (false === $pos = \strpos($path, '[')) {
                return $getter($path);
            }

            $root = \substr($path, 0, $pos);

            if (null === $data = $getter($root)) {
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
}
