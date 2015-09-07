<?hh // strict
namespace Elastica\Connection\Strategy;

use Elastica\Connection;
use Elastica\Exception\InvalidException;

type CallbackStrategyCallback = (function (array<Connection>) : Connection);

/**
 * Description of CallbackStrategy.
 *
 * @author chabior
 */
class CallbackStrategy implements StrategyInterface
{
    /**
     * @var callable
     */
    protected CallbackStrategyCallback $_callback;

    /**
     * @param callable $callback
     */
    public function __construct(CallbackStrategyCallback $callback)
    {
        $this->_callback = $callback;
    }

    /**
     * @param array<\Elastica\Connection> $connections
     *
     * @return \Elastica\Connection
     */
    public function getConnection(array<Connection> $connections) : Connection
    {
        return call_user_func_array($this->_callback, array($connections));
    }

    public static function isValid(mixed $callback) : bool
    {
        return is_callable($callback);
    }
}
