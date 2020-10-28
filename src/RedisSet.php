<?php /** @noinspection DuplicatedCode */

namespace Amp\Redis;

use Amp\AsyncGenerator;
use Amp\Pipeline;

final class RedisSet
{
    private QueryExecutor $queryExecutor;
    private string $key;

    public function __construct(QueryExecutor $queryExecutor, string $key)
    {
        $this->queryExecutor = $queryExecutor;
        $this->key = $key;
    }

    /**
     * @param string $member
     * @param string ...$members
     *
     * @return int
     */
    public function add(string $member, string ...$members): int
    {
        return $this->queryExecutor->execute(\array_merge(['sadd', $this->key, $member], $members));
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->queryExecutor->execute(['scard', $this->key]);
    }

    /**
     * @param string ...$keys
     *
     * @return array
     */
    public function diff(string ...$keys): array
    {
        return $this->queryExecutor->execute(\array_merge(['sdiff', $this->key], $keys));
    }

    /**
     * @param string $key
     * @param string ...$keys
     *
     * @return int
     */
    public function storeDiff(string $key, string ...$keys): int
    {
        return $this->queryExecutor->execute(\array_merge(['sdiffstore', $this->key, $key], $keys));
    }

    /**
     * @param string ...$keys
     *
     * @return array
     */
    public function intersect(string ...$keys): array
    {
        return $this->queryExecutor->execute(\array_merge(['sinter', $this->key], $keys));
    }

    /**
     * @param string $key
     * @param string ...$keys
     *
     * @return int
     */
    public function storeIntersection(string $key, string ...$keys): int
    {
        return $this->queryExecutor->execute(\array_merge(['sinterstore', $this->key, $key], $keys));
    }

    /**
     * @param string $member
     *
     * @return bool
     */
    public function contains(string $member): bool
    {
        return $this->queryExecutor->execute(['sismember', $this->key, $member], toBool);
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->queryExecutor->execute(['smembers', $this->key]);
    }

    /**
     * @param string $member
     * @param string $destination
     *
     * @return bool
     */
    public function move(string $member, string $destination): bool
    {
        return $this->queryExecutor->execute(['smove', $this->key, $destination, $member], toBool);
    }

    /**
     * @return string
     */
    public function popRandomMember(): string
    {
        return $this->queryExecutor->execute(['spop', $this->key]);
    }

    /**
     * @return string|null
     */
    public function getRandomMember(): ?string
    {
        return $this->queryExecutor->execute(['srandmember', $this->key]);
    }

    /**
     * @param int $count
     *
     * @return string[]
     */
    public function getRandomMembers(int $count): array
    {
        return $this->queryExecutor->execute(['srandmember', $this->key, $count]);
    }

    /**
     * @param string $member
     * @param string ...$members
     *
     * @return int
     */
    public function remove(string $member, string ...$members): int
    {
        return $this->queryExecutor->execute(\array_merge(['srem', $this->key, $member], $members));
    }

    /**
     * @param string ...$keys
     *
     * @return array
     */
    public function union(string ...$keys): array
    {
        return $this->queryExecutor->execute(\array_merge(['sunion', $this->key], $keys));
    }

    /**
     * @param string $key
     * @param string ...$keys
     *
     * @return int
     */
    public function storeUnion(string $key, string ...$keys): int
    {
        return $this->queryExecutor->execute(\array_merge(['sunionstore', $this->key, $key], $keys));
    }

    /**
     * @param string $pattern
     * @param int    $count
     *
     * @return Pipeline
     */
    public function scan(?string $pattern = null, ?int $count = null): Pipeline
    {
        return new AsyncGenerator(function () use ($pattern, $count) {
            $cursor = 0;

            do {
                $query = ['SSCAN', $this->key, $cursor];

                if ($pattern !== null) {
                    $query[] = 'MATCH';
                    $query[] = $pattern;
                }

                if ($count !== null) {
                    $query[] = 'COUNT';
                    $query[] = $count;
                }

                [$cursor, $keys] = $this->queryExecutor->execute($query);

                foreach ($keys as $key) {
                    yield $key;
                }
            } while ($cursor !== '0');
        });
    }

    /**
     * @param SortOptions $sort
     *
     * @return array
     *
     * @link https://redis.io/commands/sort
     */
    public function sort(?SortOptions $sort = null): array
    {
        return $this->queryExecutor->execute(\array_merge(['SORT', $this->key], ($sort ?? new SortOptions)->toQuery()));
    }
}
