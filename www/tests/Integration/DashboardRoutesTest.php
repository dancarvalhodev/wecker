<?php

declare(strict_types=1);

namespace Tests\Integration;

final class DashboardRoutesTest extends IntegrationTestCase
{
    public function testStopAndStartARealContainer(): void
    {
        $this->login();
        $id = $this->createRunningContainer();

        $stop = $this->request('POST', '/dashboard/stop', ['id' => $id]);
        $this->assertSame('["Container stopped"]', (string) $stop->getBody());
        $this->assertSame('exited', $this->statusInList($id));

        $start = $this->request('POST', '/dashboard/start', ['id' => $id]);
        $this->assertSame('["Container started"]', (string) $start->getBody());
        $this->assertSame('running', $this->statusInList($id));
    }

    public function testLogsShowWhatTheContainerPrinted(): void
    {
        $this->login();
        $id = $this->createRunningContainer();

        $response = $this->request('POST', '/dashboard/log', ['id' => $id]);

        $this->assertSame(["hello\n"], json_decode((string) $response->getBody(), true));
    }

    public function testInvalidContainerIdIsRejected(): void
    {
        $this->expectErrorLog();
        $this->login();

        $response = $this->request('POST', '/dashboard/stop', ['id' => '../../images/x']);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('{"success":false,"error":["Invalid container ID."]}', (string) $response->getBody());
    }

    /**
     * @param string $id
     * @return string|null the container status as the dashboard table shows it
     */
    private function statusInList(string $id): ?string
    {
        $rows = json_decode((string) $this->request('POST', '/dashboard/list')->getBody(), true)['data'];

        foreach ($rows as $row) {
            if ($row['id'] === $id) {
                return $row['status'];
            }
        }

        return null;
    }
}
