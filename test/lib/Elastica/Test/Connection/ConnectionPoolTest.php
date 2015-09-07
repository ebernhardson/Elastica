<?hh
namespace Elastica\Test\Connection;

use Elastica\Connection;
use Elastica\Connection\ConnectionPool;
use Elastica\Connection\Strategy\StrategyFactory;
use Elastica\Test\Base as BaseTest;

/**
 * @author chabior
 */
class ConnectionPoolTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $pool = $this->createPool();

        $this->assertEquals($this->getConnections(), $pool->getConnections());
    }

    /**
     * @group unit
     */
    public function testSetConnections() : void
    {
        $pool = $this->createPool();

        $connections = $this->getConnections(5);

        $pool->setConnections($connections);

        $this->assertEquals($connections, $pool->getConnections());

        $this->assertInstanceOf('Elastica\Connection\ConnectionPool', $pool->setConnections($connections));
    }

    /**
     * @group unit
     */
    public function testAddConnection() : void
    {
        $pool = $this->createPool();
        $pool->setConnections(array());

        $connections = $this->getConnections(5);

        foreach ($connections as $connection) {
            $pool->addConnection($connection);
        }

        $this->assertEquals($connections, $pool->getConnections());

        $this->assertInstanceOf('Elastica\Connection\ConnectionPool', $pool->addConnection($connections[0]));
    }

    /**
     * @group unit
     */
    public function testHasConnection() : void
    {
        $pool = $this->createPool();

        $this->assertTrue($pool->hasConnection());
    }

    /**
     * @group unit
     */
    public function testFailHasConnections() : void
    {
        $pool = $this->createPool();

        $pool->setConnections(array());

        $this->assertFalse($pool->hasConnection());
    }

    /**
     * @group unit
     */
    public function testGetConnection() : void
    {
        $pool = $this->createPool();

        $this->assertInstanceOf('Elastica\Connection', $pool->getConnection());
    }

    protected function getConnections(@int $quantity = 1) : array
    {
        $params = Map {};
        $connections = array();

        for ($i = 0; $i < $quantity; ++$i) {
            $connections[] = new Connection($params);
        }

        return $connections;
    }

    protected function createPool() : \Elastica\Connection\ConnectionPool
    {
        $connections = $this->getConnections();
        $strategy = StrategyFactory::create('Simple');

        $pool = new ConnectionPool($connections, $strategy);

        return $pool;
    }
}
