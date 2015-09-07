<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\MatchAll;
use Elastica\Test\Base as BaseTest;

class MatchAllTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $filter = new MatchAll();

        $expectedArray = array('match_all' => Map {});

        $this->assertEquals($expectedArray, $filter->toArray());
    }
}
