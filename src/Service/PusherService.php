<?php

namespace App\Service;

use RuntimeException;

class PusherService
{
    private string $appId;
    private string $key;
    private string $secret;
    private string $cluster;

    public function __construct(string $appId, string $key, string $secret, string $cluster)
    {
        $this->appId = $appId;
        $this->key = $key;
        $this->secret = $secret;
        $this->cluster = $cluster;
    }

    public function getPusher(): object
    {
        if (!class_exists(\Pusher\Pusher::class)) {
            throw new RuntimeException('Pusher PHP SDK is not installed. Install pusher/pusher-php-server or disable Pusher features.');
        }

        return new \Pusher\Pusher(
            $this->key,
            $this->secret,
            $this->appId,
            ['cluster' => $this->cluster]
        );
    }
}
