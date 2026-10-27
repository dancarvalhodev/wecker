<?php

declare(strict_types=1);

namespace Tests\Integration;

final class CreateContainerTest extends IntegrationTestCase
{
    public function testCreatesAContainerWithTheFormSettings(): void
    {
        $this->login();
        $name = $this->uniqueName();

        $response = $this->request('POST', '/dashboard/create', [
            'image' => self::TEST_IMAGE,
            'name' => $name,
            'ports' => '18081:80',
            'env' => "APP=wecker\nDEBUG=1",
            'restart' => 'unless-stopped',
        ]);

        $this->assertSame('["Container created"]', (string) $response->getBody());

        $container = json_decode((string) $this->docker->request('GET', "containers/$name/json")->getBody(), true);
        $this->assertSame(self::TEST_IMAGE, $container['Config']['Image']);
        $this->assertContains('APP=wecker', $container['Config']['Env']);
        $this->assertContains('DEBUG=1', $container['Config']['Env']);
        $this->assertSame('unless-stopped', $container['HostConfig']['RestartPolicy']['Name']);
        $this->assertSame('18081', $container['HostConfig']['PortBindings']['80/tcp'][0]['HostPort']);
    }

    public function testNameInUseIsRejected(): void
    {
        $this->expectErrorLog();
        $this->login();
        $form = ['image' => self::TEST_IMAGE, 'name' => $this->uniqueName(), 'restart' => 'no'];

        $this->request('POST', '/dashboard/create', $form);
        $second = $this->request('POST', '/dashboard/create', $form);

        $this->assertSame(422, $second->getStatusCode());
        $this->assertSame('{"success":false,"error":{"name":"Container name already in use"}}', (string) $second->getBody());
    }

    public function testInvalidFormIsRejected(): void
    {
        $this->expectErrorLog();
        $this->login();

        $response = $this->request('POST', '/dashboard/create', [
            'image' => '',
            'ports' => '80',
            'env' => 'no-equals-sign',
            'restart' => 'sometimes',
        ]);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame(
            ['image', 'ports', 'env', 'restart'],
            array_keys(json_decode((string) $response->getBody(), true)['error']),
        );
    }

    private function uniqueName(): string
    {
        $name = 'wecker-test-' . bin2hex(random_bytes(4));
        $this->removeAfterTest($name);

        return $name;
    }
}
