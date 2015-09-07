<?hh
namespace Elastica\Test\Connection\Strategy;

use Elastica\Connection;
use Elastica\Connection\Strategy\RoundRobin;
use Elastica\Exception\ConnectionException;
use Elastica\Response;
use Elastica\Test\Base;

/**
 * Description of RoundRobinTest.
 *
 * @author chabior
 */
class RoundRobinTest extends Base
{
    /**
     * @var int Number of seconds to wait before timeout is called. Is set low for tests to have fast tests.
     */
    protected int $_timeout = 1;

    /**
     * @group functional
     */
    public function testConnection() : void
    {
        $config = array('connectionStrategy' => 'RoundRobin');
        $client = $this->_getClient($config);
        $response = $client->request('/_aliases');
        /* @var $response Response */

        $this->_checkResponse($response);

        $this->_checkStrategy($client);
    }

    /**
     * @group unit
     */
    public function testOldStrategySetted() : void
    {
        $config = array('roundRobin' => true);
        $client = $this->_getClient($config);

        $this->_checkStrategy($client);
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\ConnectionException
     */
    public function testFailConnection() : void
    {
        $config = array('connectionStrategy' => 'RoundRobin', 'host' => '255.255.255.0', 'timeout' => $this->_timeout);
        $client = $this->_getClient($config);

        $this->_checkStrategy($client);

        $client->request('/_aliases')->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testWithOneFailConnection() : void
    {
        $connections = array(
            new Connection(Map {'host' => '255.255.255.0', 'timeout' => $this->_timeout}),
            new Connection(Map {'host' => $this->_getHost(), 'timeout' => $this->_timeout}),
        );

        $count = 0;
        $callback = function ($connection, $exception, $client) use (&$count) {
            ++$count;
        };

        $client = $this->_getClient(array('connectionStrategy' => 'RoundRobin'), $callback);
        $client->setConnections($connections);

        $response = $client->request('/_aliases');
        /* @var $response Response */

        $this->_checkResponse($response);

        $this->_checkStrategy($client);

        $this->assertLessThan(count($connections), $count);
    }

    /**
     * @group functional
     */
    public function testWithNoValidConnection() : void
    {
        $connections = array(
            new Connection(Map {'host' => '255.255.255.0', 'timeout' => $this->_timeout}),
            new Connection(Map {'host' => '45.45.45.45', 'port' => '80', 'timeout' => $this->_timeout}),
            new Connection(Map {'host' => '10.123.213.123', 'timeout' => $this->_timeout}),
        );

        $count = 0;
        $client = $this->_getClient(array('roundRobin' => true), function () use (&$count) {
            ++$count;
        });

        $client->setConnections($connections);

        try {
            $client->request('/_aliases')->getWaitHandle()->join();
            $this->fail('Should throw exception as no connection valid');
        } catch (ConnectionException $e) {
            $this->assertEquals(count($connections), $count);
            $this->_checkStrategy($client);
        }
    }

    protected function _checkStrategy(@\Elastica\Client $client) : void
    {
        $strategy = $client->getConnectionStrategy();

        $this->assertInstanceOf('Elastica\Connection\Strategy\RoundRobin', $strategy);
    }

    protected function _checkResponse($response) : void
    {
        if ($response instanceof Awaitable) {
            $response = $response->getWaitHandle()->join();
        }
        $this->assertTrue($response->isOk());
    }
}
