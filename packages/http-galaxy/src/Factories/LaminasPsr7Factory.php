<?php

declare(strict_types=1);

/*
 * This code is under BSD 3-Clause "New" or "Revised" License.
 *
 * PHP version 7 and above required
 *
 * @category  HttpManager
 *
 * @author    Divine Niiquaye Ibok <divineibok@gmail.com>
 * @copyright 2019 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * @link      https://www.biurad.com/projects/httpmanager
 * @since     Version 0.1
 */

namespace BiuradPHP\Http\Factories;

use Laminas\Diactoros\RequestFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laminas\Diactoros\UriFactory;
use Psr\Http\Message\ServerRequestInterface;

use function Laminas\Diactoros\normalizeServer;
use function Laminas\Diactoros\normalizeUploadedFiles;
use function Laminas\Diactoros\marshalHeadersFromSapi;
use function Laminas\Diactoros\parseCookieHeader;
use function Laminas\Diactoros\marshalUriFromSapi;
use function Laminas\Diactoros\marshalMethodFromSapi;
use function Laminas\Diactoros\marshalProtocolVersionFromSapi;

/**
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
final class LaminasPsr7Factory extends Psr17Factory
{
    /**
     * Function to use to get apache request headers; present only to simplify mocking.
     *
     * @var callable
     */
    private static $apacheRequestHeaders = 'apache_request_headers';

    /**
     * {@inheritdoc}
     */
    protected function setFactoryCandidates(): void
    {
        $this->responseFactoryClass         = ResponseFactory::class;
        $this->serverRequestFactoryClass    = ServerRequestFactory::class;
        $this->requestFactoryClass          = RequestFactory::class;
        $this->uploadedFilesFactoryClass    = UploadedFileFactory::class;
        $this->streamFactoryClass           = StreamFactory::class;
        $this->uriFactoryClass              = UriFactory::class;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromGlobalRequest(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) : ServerRequestInterface {
        $server = normalizeServer(
            $server ?: $_SERVER,
            is_callable(self::$apacheRequestHeaders) ? self::$apacheRequestHeaders : 'getallheaders'
        );
        $files   = normalizeUploadedFiles($files ?: $_FILES);
        $headers = self::getHeaders(marshalHeadersFromSapi($server));

        if (null === $cookies && array_key_exists('cookie', $headers)) {
            $cookies = parseCookieHeader($headers['cookie']);
        }

        return new ServerRequest(
            $server,
            $files,
            marshalUriFromSapi($server, $headers),
            marshalMethodFromSapi($server),
            'php://input',
            $headers,
            $cookies ?: $_COOKIE,
            $query ?: $_GET,
            $body ?: $_POST,
            marshalProtocolVersionFromSapi($server)
        );
    }

    /**
     * Gets the HTTP headers.
     *
     * @return array
     */
    private static function getHeaders(array $parameters): array
    {
        $headers = [];
        foreach ($parameters as $key => $value) {
            $headers[$key] = $value;
        }

        if (isset($parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($parameters['PHP_AUTH_PW']) ? $parameters['PHP_AUTH_PW'] : '';
        } else {
            /*
             * php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
             * For this workaround to work, add these lines to your .htaccess file:
             * RewriteCond %{HTTP:Authorization} .+
             * RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
             *
             * A sample .htaccess file:
             * RewriteEngine On
             * RewriteCond %{HTTP:Authorization} .+
             * RewriteRule ^ - [E=HTTP_AUTHORIZATION:%0]
             * RewriteCond %{REQUEST_FILENAME} !-f
             * RewriteRule ^(.*)$ app.php [QSA,L]
             */

            $authorizationHeader = null;
            if (isset($parameters['AUTHORIZATION'])) {
                $authorizationHeader = $parameters['AUTHORIZATION'];
            }

            if (null !== $authorizationHeader) {
                if (0 === stripos($authorizationHeader, 'basic ')) {
                    // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
                    $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)), 2);
                    if (2 == count($exploded)) {
                        [$headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']] = $exploded;
                    }
                } elseif (empty($parameters['PHP_AUTH_DIGEST']) && (0 === stripos($authorizationHeader, 'digest '))) {
                    // In some circumstances PHP_AUTH_DIGEST needs to be set
                    $headers['PHP_AUTH_DIGEST'] = $authorizationHeader;
                    $parameters['PHP_AUTH_DIGEST'] = $authorizationHeader;
                } elseif (0 === stripos($authorizationHeader, 'bearer ')) {
                    /*
                     * XXX: Since there is no PHP_AUTH_BEARER in PHP predefined variables,
                     *      I'll just set $headers['AUTHORIZATION'] here.
                     *      https://php.net/reserved.variables.server
                     */
                    $headers['AUTHORIZATION'] = $authorizationHeader;
                }
            }
        }

        if (isset($headers['AUTHORIZATION'])) {
            return $headers;
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic '.base64_encode($headers['PHP_AUTH_USER'].':'.$headers['PHP_AUTH_PW']);
        } elseif (isset($headers['PHP_AUTH_DIGEST'])) {
            $headers['AUTHORIZATION'] = $headers['PHP_AUTH_DIGEST'];
        }

        return $headers;
    }
}
