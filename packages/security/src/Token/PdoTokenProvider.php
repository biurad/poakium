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

namespace Biurad\Security\Token;

use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony\Component\Security\Core\Authentication\RememberMe\PersistentTokenInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface;
use Symfony\Component\Security\Core\Authentication\RememberMe\TokenVerifierInterface;
use Symfony\Component\Security\Core\Exception\TokenNotFoundException;

/**
 * Token provider for persistent login tokens.
 *
 * @author Divine Niiquaye Ibok <divineibok@gmail.com>
 */
class PdoTokenProvider implements TokenProviderInterface, TokenVerifierInterface
{
    private \PDO $connection;

    /**
     * @param string|\PDO $connection A PDO instance or a DSN string
     */
    public function __construct($connection)
    {
        if (!$connection instanceof \PDO) {
            $connection = $this->createPdoFromDsm($connection);
        }

        // If table does not exist, create it
        $connection->exec('CREATE TABLE IF NOT EXISTS rememberme_token (
            series VARCHAR(88)      UNIQUE PRIMARY KEY NOT NULL,
            value  VARCHAR(88)      NOT NULL,
            last_used DATETIME      NOT NULL,
            class   VARCHAR(100)    NOT NULL,
            identifier VARCHAR(200) NOT NULL
        )');
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function loadTokenBySeries(string $series): PersistentTokenInterface
    {
        $stmt = $this->connection->prepare('SELECT class, identifier, value, last_used FROM rememberme_token WHERE series = ?');
        $stmt->execute([$series]);

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            return new PersistentToken($row['class'], $row['identifier'], $series, $row['value'], new \DateTime($row['last_used']));
        }

        throw new TokenNotFoundException('No token found.');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTokenBySeries(string $series): void
    {
        $stmt = $this->connection->prepare('DELETE FROM rememberme_token WHERE series = ?');
        $stmt->execute([$series]);
    }

    /**
     * {@inheritdoc}
     */
    public function updateToken(string $series, string $tokenValue, \DateTime $lastUsed): void
    {
        $stmt = $this->connection->prepare('UPDATE rememberme_token SET value = ?, last_used = ? WHERE series = ?');
        $updated = $stmt->execute([$tokenValue, $lastUsed->format('Y-m-d H:i:s'), $series]);

        if (!$updated) {
            throw new TokenNotFoundException('No token found.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createNewToken(PersistentTokenInterface $token): void
    {
        $stmt = $this->connection->prepare('INSERT INTO rememberme_token (series, value, last_used, class, identifier) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$token->getSeries(), $token->getTokenValue(), $token->getLastUsed()->format('Y-m-d H:i:s'), $token->getClass(), $token->getUserIdentifier()]);
    }

    /**
     * {@inheritdoc}
     */
    public function verifyToken(PersistentTokenInterface $token, string $tokenValue): bool
    {
        if (\hash_equals($token->getTokenValue(), $tokenValue)) {
            return true;
        }

        try {
            $pToken = $this->loadTokenBySeries($token->getSeries());
        } catch (TokenNotFoundException $e) {
            return false;
        }

        if ($pToken->getLastUsed()->getTimestamp() + 60 < time()) {
            return false;
        }

        return \hash_equals($pToken->getTokenValue(), $tokenValue);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function updateExistingToken(PersistentTokenInterface $token, string $tokenValue, \DateTimeInterface $lastUsed): void
    {
        $this->connection->beginTransaction();

        try {
            $this->deleteTokenBySeries($token->getSeries());
            $this->createNewToken(new PersistentToken($token->getClass(), $token->getUserIdentifier(), $token->getSeries(), $token->getTokenValue(), $lastUsed));
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    protected function createPdoFromDsm(string $connection): \PDO
    {
        if (!\str_contains($connection, 'username=')) {
            throw new \InvalidArgumentException('The DSN must contain a username.');
        }

        if (!\str_contains($connection, 'password=')) {
            throw new \InvalidArgumentException('The DSN must contain a password.');
        }

        [$connection, $username] = \explode('username=', $connection);
        [,$password] = \explode('password=', $username);

        if (\str_ends_with($password, ';')) {
            throw new \InvalidArgumentException('The DSN must not contain a ; after the password.');
        }

        return new \PDO($connection, \substr($username, 0, \strpos($username, ';')), $password);
    }
}
