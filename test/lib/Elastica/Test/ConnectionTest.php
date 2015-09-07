<?hh
namespace Elastica\Test;

use Elastica\Connection;
use Elastica\Request;
use Elastica\Test\Base as BaseTest;

class ConnectionTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testEmptyConstructor() : void
    {
        $connection = new Connection();
        $this->assertEquals(Connection::DEFAULT_HOST, $connection->getHost());
        $this->assertEquals(Connection::DEFAULT_PORT, $connection->getPort());
        $this->assertEquals(Connection::DEFAULT_TRANSPORT, $connection->getTransport());
        $this->assertInstanceOf('Elastica\Transport\AbstractTransport', $connection->getTransportObject());
        $this->assertEquals(Connection::TIMEOUT, $connection->getTimeout());
        $this->assertEquals(Connection::CONNECT_TIMEOUT, $connection->getConnectTimeout());
        $this->assertEquals(array(), $connection->getFullConfig());
        $this->assertTrue($connection->isEnabled());
    }

    /**
     * @group unit
     */
    public function testEnabledDisable() : void
    {
        $connection = new Connection();
        $this->assertTrue($connection->isEnabled());
        $connection->setEnabled(false);
        $this->assertFalse($connection->isEnabled());
        $connection->setEnabled(true);
        $this->assertTrue($connection->isEnabled());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\ConnectionException
     */
    public function testInvalidConnection() : void
    {
        $connection = new Connection(Map {'port' => 39999});

        $request = new Request('_status', Request::GET);
        $request->setConnection($connection);

        // Throws exception because no valid connection
        $request->send()->getWaitHandle()->join();
    }

    /**
     * @group unit
     */
    public function testCreate() : void
    {
        $connection = Connection::create();
        $this->assertInstanceOf('Elastica\Connection', $connection);

        $connection = Connection::create(Map {});
        $this->assertInstanceOf('Elastica\Connection', $connection);

        $port = 9999;
        $connection = Connection::create(Map {'port' => $port});
        $this->assertInstanceOf('Elastica\Connection', $connection);
        $this->assertEquals($port, $connection->getPort());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testCreateInvalid() : void
    {
        Connection::create('test');
    }

    /**
     * @group unit
     */
    public function testGetConfig() : void
    {
        $url = 'test';
        $connection = new Connection(Map {'config' => Map {'url' => $url}});
        $this->assertTrue($connection->hasConfig('url'));
        $this->assertEquals($url, $connection->getConfig('url'));
    }

    /**
     * @group unit
     */
    public function testGetConfigWithArrayUsedForTransport() : void
    {
        $connection = new Connection(Map {'transport' => Map {'type' => 'Http'}});
        $this->assertInstanceOf('Elastica\Transport\Http', $connection->getTransportObject());
    }

    /**
     * @group unit
     * @expectedException Elastica\Exception\InvalidException
     * @expectedExceptionMessage Invalid transport
     */
    public function testGetInvalidConfigWithArrayUsedForTransport() : void
    {
        $connection = new Connection(Map {'transport' => Map {'type' => 'invalidtransport'}});
        $connection->getTransportObject();
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testGetConfigInvalidValue() : void
    {
        $connection = new Connection();
        $connection->getConfig('url');
    }
}
