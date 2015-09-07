<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\Exists;
use Elastica\Test\Base as BaseTest;

class ExistsTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $field = 'test';
        $filter = new Exists($field);

        $expectedArray = array('exists' => Map {'field' => $field});
        $this->assertEquals($expectedArray, $filter->toArray());
    }

    /**
     * @group unit
     */
    public function testSetField() : void
    {
        $field = 'test';
        $filter = new Exists($field);

        $this->assertEquals($field, $filter->getParam('field'));

        $newField = 'hello world';
        $this->assertInstanceOf('Elastica\Filter\Exists', $filter->setField($newField));

        $this->assertEquals($newField, $filter->getParam('field'));
    }
}
