<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Terms;
use Elastica\Document;
use Elastica\Query;

class TermsTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();

        $index->getType('test')->addDocuments(array(
            new Document('1', array('color' => 'blue')),
            new Document('2', array('color' => 'blue')),
            new Document('3', array('color' => 'red')),
            new Document('4', array('color' => 'green')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group functional
     */
    public function testTermsAggregation() : void
    {
        $agg = new Terms('terms');
        $agg->setField('color');

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('terms');

        $this->assertEquals(2, $results['buckets'][0]['doc_count']);
        $this->assertEquals('blue', $results['buckets'][0]['key']);
    }

    /**
     * @group functional
     */
    public function testTermsSetOrder() : void
    {
        $agg = new Terms('terms');
        $agg->setField('color');
        $agg->setOrder('_count', 'asc');

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('terms');

        $this->assertEquals('blue', $results['buckets'][2]['key']);
    }
}
