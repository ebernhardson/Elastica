<?hh
namespace Elastica\Test\Query;

use Elastica\Query\Term;
use Elastica\Test\Base as BaseTest;

class TermTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new Term();
        $key = 'name';
        $value = 'nicolas';
        $boost = 2.0;
        $query->setTerm($key, $value, $boost);

        $data = $query->toArray();

        $this->assertInstanceOf('HH\Map', $data['term']);
        $this->assertInstanceOf('HH\Map', /* UNSAFE_EXPR */ $data['term'][$key]);
        $this->assertEquals(/* UNSAFE_EXPR */ $data['term'][$key]['value'], $value);
        $this->assertEquals(/* UNSAFE_EXPR */ $data['term'][$key]['boost'], $boost);
    }
}
