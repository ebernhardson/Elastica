<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\Term;
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
        $value = 'ruflin';
        $query->setTerm($key, $value);

        $data = $query->toArray();

        $this->assertEquals(Map {$key => $value}, $data['term']);
    }
}
