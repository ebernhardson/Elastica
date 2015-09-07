<?hh // strict
namespace Elastica\Connection\Strategy;

use Elastica\Connection;

/**
 * Description of AbstractStrategy.
 *
 * @author chabior
 */
interface StrategyInterface
{
    /**
     * @param array<\Elastica\Connection> $connections
     *
     * @return \Elastica\Connection
     */
    public function getConnection(array<Connection> $connections) : Connection;
}
