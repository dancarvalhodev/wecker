<?php

use App\Service\Api\DockerClient;
use App\Service\Api\DockerClientFactory;

return [
    DockerClient::class => function (DockerClientFactory $factory) {
        return new DockerClient($factory->create());
    },
];
