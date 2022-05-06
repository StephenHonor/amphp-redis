<?php

namespace Amp\Redis\Connection;

use Amp\ByteStream\ClosedException;
use Amp\Pipeline\ConcurrentIterator;
use Amp\Pipeline\Queue;
use Amp\Socket\Socket;
use Revolt\EventLoop;

final class DefaultRespSocket implements RespSocket
{
    private readonly Socket $socket;

    private readonly ConcurrentIterator $iterator;

    public function __construct(Socket $socket)
    {
        $this->socket = $socket;

        $queue = new Queue();
        $this->iterator = $queue->iterate();

        EventLoop::queue(static function () use ($socket, $queue): void {
            $parser = new RespParser($queue);

            try {
                while (null !== $chunk = $socket->read()) {
                    $parser->append($chunk);
                }

                $queue->complete();
            } catch (\Throwable $e) {
                $queue->error($e);
            }

            $socket->close();
        });
    }

    public function read(): ?RespPayload
    {
        if (!$this->iterator->continue()) {
            return null;
        }

        return $this->iterator->getValue();
    }

    public function write(string ...$args): void
    {
        if ($this->socket->isClosed()) {
            throw new ClosedException('Redis connection already closed');
        }

        $payload = \implode("\r\n", \array_map(fn (string $arg) => '$' . \strlen($arg) . "\r\n" . $arg, $args));
        $payload = '*' . \count($args) . "\r\n{$payload}\r\n";

        $this->socket->write($payload);
    }

    public function reference(): void
    {
        $this->socket->reference();
    }

    public function unreference(): void
    {
        $this->socket->unreference();
    }

    public function close(): void
    {
        $this->socket->close();
    }

    public function isClosed(): bool
    {
        return $this->socket->isClosed();
    }

    public function __destruct()
    {
        $this->close();
    }
}