<?hh
namespace Elastica\Connection;

use Elastica\Client;
use Elastica\Connection;
use Elastica\Connection\Strategy\StrategyInterface;
use Exception;

type ConnectionPoolCallback = (function (Connection, Exception, Client) : void);

/**
 * Description of ConnectionPool.
 *
 * @author chabior
 */
class ConnectionPool
{
    /**
     * @var array<\Elastica\Connection> Connections array
     */
    protected array<Connection> $_connections;

    /**
     * @var \Elastica\Connection\Strategy\StrategyInterface Strategy for connection
     */
    protected StrategyInterface $_strategy;

    /**
     * @var callback Function called on connection fail
     */
    protected ?ConnectionPoolCallback $_callback;

    /**
     * @param array<Connection>                               $connections
     * @param \Elastica\Connection\Strategy\StrategyInterface $strategy
     * @param callback|null                                   $callback
     */
    public function __construct(array<Connection> $connections, StrategyInterface $strategy, ?ConnectionPoolCallback $callback = null)
    {
        $this->_connections = $connections;
        $this->_strategy = $strategy;
        $this->_callback = $callback;
    }

    /**
     * @param \Elastica\Connection $connection
     *
     * @return $this
     */
    public function addConnection(Connection $connection) : this
    {
        $this->_connections[] = $connection;

        return $this;
    }

    /**
     * @param array|\Elastica\Connection[] $connections
     *
     * @return $this
     */
    public function setConnections(array $connections) : this
    {
        $this->_connections = $connections;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasConnection() : bool
    {
        foreach ($this->_connections as $connection) {
            if ($connection->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getConnections() : array<Connection>
    {
        return $this->_connections;
    }

    /**
     * @throws \Elastica\Exception\ClientException
     *
     * @return \Elastica\Connection
     */
    public function getConnection() : Connection
    {
        return $this->_strategy->getConnection($this->getConnections());
    }

    /**
     * @param \Elastica\Connection $connection
     * @param \Exception           $e
     * @param Client               $client
     */
    public function onFail(Connection $connection, Exception $e, Client $client) : void
    {
        $connection->setEnabled(false);

        $callback = $this->_callback;
        if ($callback !== null) {
            call_user_func($callback, $connection, $e, $client);
        }
    }

    /**
     * @return \Elastica\Connection\Strategy\StrategyInterface
     */
    public function getStrategy() : StrategyInterface
    {
        return $this->_strategy;
    }
}
