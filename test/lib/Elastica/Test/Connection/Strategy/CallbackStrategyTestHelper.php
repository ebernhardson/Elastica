<?hh
namespace Elastica\Test\Connection\Strategy;

use Elastica\Connection;

class CallbackStrategyTestHelper
{
    public function __invoke($connections) : Connection
    {
        return $connections[0];
    }

    public function getFirstConnection($connections) : Connection
    {
        return $connections[0];
    }

    public static function getFirstConnectionStatic($connections) : Connection
    {
        return $connections[0];
    }
}
