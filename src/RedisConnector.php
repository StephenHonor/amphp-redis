<?php

namespace Amp\Redis;

use Amp\Cancellation;
use Amp\Socket\ConnectContext;

interface RedisConnector
{
    /**
     * @throws RedisException
     */
    public function connect(
        Config $config,
        ?ConnectContext $context = null,
        ?Cancellation $cancellation = null,
    ): RespSocket;
}
