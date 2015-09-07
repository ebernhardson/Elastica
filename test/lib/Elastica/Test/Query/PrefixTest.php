<?hh
namespace Elastica\Test\Query;

use Elastica\Query\Prefix;
use Elastica\Test\Base as BaseTest;

class PrefixTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new Prefix();
        $key = 'name';
        $value = 'ni';
        $boost = 2.0;
        $query->setPrefix($key, $value, $boost);

        $data = $query->toArray();

        $this->assertInstanceOf('HH\Map', $data['prefix']);
        $this->assertInstanceOf('HH\Map', /* UNSAFE_EXPR */ $data['prefix'][$key]);
        $this->assertEquals(/* UNSAFE_EXPR */ $data['prefix'][$key]['value'], $value);
        $this->assertEquals(/* UNSAFE_EXPR */ $data['prefix'][$key]['boost'], $boost);
    }
}
