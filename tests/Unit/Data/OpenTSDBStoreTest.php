<?php

/**
 * OpenTSDBStoreTest.php
 *
 * -Description-
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * @link       https://www.librenms.org
 *
 * @copyright  2018 Tony Murray
 * @author     Tony Murray <murraytony@gmail.com>
 */

namespace LibreNMS\Tests\Unit\Data;

use Carbon\Carbon;
use LibreNMS\Config;
use LibreNMS\Data\Store\OpenTSDB;
use LibreNMS\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('datastores')]
class OpenTSDBStoreTest extends TestCase
{
    protected $timestamp = 990464400;

    protected function setUp(): void
    {
        parent::setUp();

        // fix the date
        Carbon::setTestNow(Carbon::createFromTimestampUTC($this->timestamp));
        Config::set('opentsdb.enable', true);
    }

    protected function tearDown(): void
    {
        // restore Carbon:now() to normal
        Carbon::setTestNow();
        Config::set('opentsdb.enable', false);

        parent::tearDown();
    }

    public function testSocketConnectError(): void
    {
        $mockFactory = \Mockery::mock(\Socket\Raw\Factory::class);

        $mockFactory->shouldReceive('createClient')
            ->andThrow('Socket\Raw\Exception', 'Failed to handle connect exception')->once();

        new OpenTSDB($mockFactory);
    }

    public function testSocketWriteError(): void
    {
        $mockSocket = \Mockery::mock(\Socket\Raw\Socket::class);
        $opentsdb = $this->mockOpenTSDB($mockSocket);

        $mockSocket->shouldReceive('write')
            ->andThrow('Socket\Raw\Exception', 'Did not handle socket exception')->once();

        $opentsdb->put(['hostname' => 'test'], 'fake', [], ['one' => 1]);
    }

    public function testSimpleWrite(): void
    {
        $mockSocket = \Mockery::mock(\Socket\Raw\Socket::class);
        $opentsdb = $this->mockOpenTSDB($mockSocket);

        $device = ['hostname' => 'testhost'];
        $measurement = 'testmeasure';
        $tags = ['ifName' => 'testifname', 'type' => 'testtype'];
        $fields = ['ifIn' => 234234, 'ifOut' => 53453];

        $mockSocket->shouldReceive('write')
            ->with("put net.testmeasure $this->timestamp 234234.000000 hostname=testhost ifName=testifname type=testtype key=ifIn\n")->once();
        $mockSocket->shouldReceive('write')
            ->with("put net.testmeasure $this->timestamp 53453.000000 hostname=testhost ifName=testifname type=testtype key=ifOut\n")->once();
        $opentsdb->put($device, $measurement, $tags, $fields);
    }

    public function testPortWrite(): void
    {
        $mockSocket = \Mockery::mock(\Socket\Raw\Socket::class);
        $opentsdb = $this->mockOpenTSDB($mockSocket);

        $device = ['hostname' => 'testhost'];
        $measurement = 'ports';
        $tags = ['ifName' => 'testifname', 'type' => 'testtype'];
        $fields = ['ifIn' => 897238, 'ifOut' => 2342];

        $mockSocket->shouldReceive('write')
            ->with("put net.port.ifin $this->timestamp 897238.000000 hostname=testhost ifName=testifname type=testtype\n")->once();
        $mockSocket->shouldReceive('write')
            ->with("put net.port.ifout $this->timestamp 2342.000000 hostname=testhost ifName=testifname type=testtype\n")->once();
        $opentsdb->put($device, $measurement, $tags, $fields);
    }

    /**
     * @param  mixed  $mockSocket
     * @return OpenTSDB
     */
    private function mockOpenTSDB($mockSocket)
    {
        $mockFactory = \Mockery::mock(\Socket\Raw\Factory::class);

        $mockFactory->shouldReceive('createClient')
            ->andReturn($mockSocket);

        return new OpenTSDB($mockFactory);
    }
}
