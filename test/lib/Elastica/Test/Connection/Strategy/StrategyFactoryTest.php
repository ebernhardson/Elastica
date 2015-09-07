<?hh
namespace Elastica\Test\Connection\Strategy;

use Elastica\Connection\Strategy\StrategyFactory;
use Elastica\Test\Connection\Strategy\EmptyStrategy;
use Elastica\Test\Base;

/**
 * Description of StrategyFactoryTest.
 *
 * @author chabior
 */
class StrategyFactoryTest extends Base
{
    /**
     * @group unit
     */
    public function testCreateCallbackStrategy() : void
    {
        $callback = function ($connections) {
        };

        $strategy = StrategyFactory::create($callback);

        $this->assertInstanceOf('Elastica\Connection\Strategy\CallbackStrategy', $strategy);
    }

    /**
     * @group unit
     */
    public function testCreateByName() : void
    {
        $strategyName = 'Simple';

        $strategy = StrategyFactory::create($strategyName);

        $this->assertInstanceOf('Elastica\Connection\Strategy\Simple', $strategy);
    }

    /**
     * @group unit
     */
    public function testCreateByClass() : void
    {
        $strategy = new EmptyStrategy();

        $this->assertEquals($strategy, StrategyFactory::create($strategy));
    }

    /**
     * @group unit
     * @expectedException \InvalidArgumentException
     */
    public function testFailCreate() : void
    {
        $strategy = new \stdClass();

        StrategyFactory::create($strategy);
    }

    /**
     * @group unit
     */
    public function testNoCollisionWithGlobalNamespace() : void
    {
        // create collision
        if (!class_exists('Simple')) {
            class_alias('Elastica\Util', 'Simple');
        }
        $strategy = StrategyFactory::create('Simple');
        $this->assertInstanceOf('Elastica\Connection\Strategy\Simple', $strategy);
    }
}
