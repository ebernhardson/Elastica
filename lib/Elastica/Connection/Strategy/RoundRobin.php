<?hh // strict
namespace Elastica\Connection\Strategy;

use Elastica\Connection;

/**
 * Description of RoundRobin.
 *
 * @author chabior
 */
class RoundRobin extends Simple
{
    /**
     * @param array|\Elastica\Connection[] $connections
     *
     * @throws \Elastica\Exception\ClientException
     *
     * @return \Elastica\Connection
     */
    public function getConnection(array<Connection> $connections) : Connection
    {
        shuffle($connections);

        return parent::getConnection($connections);
    }
}
