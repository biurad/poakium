<?php declare(strict_types=1);

/*
 * This file is part of Biurad opensource projects.
 *
 * @copyright 2022 Biurad Group (https://biurad.com/)
 * @license   https://opensource.org/licenses/BSD-3-Clause License
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Biurad\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Class ServerRequest.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @param string                               $method       HTTP method
     * @param string|UriInterface                  $uri          URI
     * @param array                                $headers      Request headers
     * @param resource|StreamInterface|string|null $body         Request body
     * @param string                               $version      Protocol version
     * @param array<string,mixed>                  $serverParams Typically the $_SERVER superglobal
     */
    public function __construct(string $method, $uri, array $headers = [], $body = null, string $version = '1.1', array $serverParams = [])
    {
        parent::__construct($method, $uri, $headers + $serverParams, $body, $version);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($name, $default = null)
    {
        return $this->message->attributes->get($name, $default);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes()
    {
        return $this->message->attributes->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams()
    {
        return $this->message->cookies->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->message->request->count() > 0 ? $this->message->request->all() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams()
    {
        return $this->message->query->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams()
    {
        return $this->message->server->all();
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles()
    {
        return \array_map(fn (\Symfony\Component\HttpFoundation\File\UploadedFile $v) => new UploadedFile($v->getUploadedFile()->getPath(), $v->getSize(), $v->getError(), $v->getClientFilename(), $v->getClientMediaType()), $this->message->files->all());
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute($name, $value): self
    {
        $new = clone $this;
        $new->message->attributes->set($name, $value);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute($name): self
    {
        $new = clone $this;
        $new->message->attributes->remove($name);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies): self
    {
        $new = clone $this;
        $new->message->cookies->replace($cookies);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data): self
    {
        if ($data instanceof \JsonSerializable) {
            $data = $data->jsonSerialize();
        } elseif ($data instanceof \stdClass) {
            $data = (array) $data;
        } elseif ($data instanceof \Traversable) {
            $data = \iterator_to_array($data);
        } elseif (!\is_array($data)) {
            throw new \InvalidArgumentException('Parsed body must be an array, an object implementing ArrayAccess, stdClass object, or a JsonSerializable object.');
        }

        $new = clone $this;
        $new->message->request->replace($data);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query): self
    {
        $new = clone $this;
        $new->message->query->replace($query);

        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles): self
    {
        foreach ($uploadedFiles as $offset => $uploadedFile) {
            if ($uploadedFile instanceof UploadedFile) {
                $uploadedFiles[$offset] = $uploadedFile->getUploadedFile();
                continue;
            }
            $uploadedFiles[$offset] = [
                'error' => $uploadedFile->getError(),
                'name' => $uploadedFile->getClientFilename(),
                'type' => $uploadedFile->getClientMediaType(),
                'tmp_name' => $uploadedFile->getStream()->getMetadata('uri'),
                'size' => $uploadedFile->getSize(),
            ];
        }
        $new = clone $this;
        $new->message->files->replace($uploadedFiles);

        return $new;
    }
}
