<?hh
namespace Elastica\Test\Query;

use Elastica\Query\Regexp;
use Elastica\Test\Base as BaseTest;

class RegexpTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $field = 'name';
        $value = 'ruf';
        $boost = 2.0;

        $query = new Regexp($field, $value, $boost);

        $expectedArray = array(
            'regexp' => Map {
                $field => array(
                    'value' => $value,
                    'boost' => $boost,
                ),
            },
        );

        $this->assertequals($expectedArray, $query->toArray());
    }
}
