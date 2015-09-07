<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\BoolNot;
use Elastica\Filter\Ids;
use Elastica\Test\Base as BaseTest;

class BoolNotTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $idsFilter = new Ids();
        $idsFilter->setIds(12);
        $filter = new BoolNot($idsFilter);

        $expectedArray = array(
            'not' => Map {
                'filter' => $idsFilter->toArray(),
            },
        );

        $this->assertEquals($expectedArray, $filter->toArray());
    }
}
