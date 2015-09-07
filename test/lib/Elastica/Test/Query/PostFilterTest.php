<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Filter\Term;
use Elastica\Query;
use Elastica\Query\Match;
use Elastica\Test\Base as BaseTest;

class PostFilterTest extends BaseTest
{
    protected function _getTestIndex() : \Elastica\Index
    {
        $index = $this->_createIndex();
        $docs = array(
            new Document('1', array('color' => 'green', 'make' => 'ford')),
            new Document('2', array('color' => 'blue', 'make' => 'volvo')),
            new Document('3', array('color' => 'red', 'make' => 'ford')),
            new Document('4', array('color' => 'green', 'make' => 'renault')),
        );
        $index->getType('test')->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new Query();

        $post_filter = new Term(Map {'color' => 'green'});
        $query->setPostFilter($post_filter);

        $data = $query->toArray();

		$this->assertInstanceOf('HH\Map', $data);
		$this->assertTrue(/* UNSAFE_EXPR */ $data->contains('post_filter'));
        $this->assertEquals(array('term' => Map {'color' => 'green'}), $data['post_filter']);
    }

    /**
     * @group functional
     */
    public function testQuery() : void
    {
        $query = new Query();

        $match = new Match();
        $match->setField('make', 'ford');

        $query->setQuery($match);

        $filter = new Term();
        $filter->setTerm('color', 'green');

        $query->setPostFilter($filter);

        $results = $this->_getTestIndex()->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $results->getTotalHits());
    }
}
