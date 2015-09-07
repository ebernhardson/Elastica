<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\NumericRange;
use Elastica\Test\Base as BaseTest;

class NumericRangeTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testAddField() : void
    {
        $rangeFilter = new NumericRange();
        $returnValue = $rangeFilter->addField('fieldName', array('to' => 'value'));
        $this->assertInstanceOf('Elastica\Filter\NumericRange', $returnValue);
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $filter = new NumericRange();

        $fromTo = array('from' => 'ra', 'to' => 'ru');
        $filter->addField('name', $fromTo);

        $expectedArray = array(
            'numeric_range' => Map {
                'name' => $fromTo,
            },
        );

        $this->assertEquals($expectedArray, $filter->toArray());
    }
}
