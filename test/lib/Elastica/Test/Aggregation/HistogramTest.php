<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Histogram;
use Elastica\Document;
use Elastica\Query;

class HistogramTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();

        $index->getType('test')->addDocuments(array(
            new Document('1', array('price' => 5, 'color' => 'blue')),
            new Document('2', array('price' => 8, 'color' => 'blue')),
            new Document('3', array('price' => 1, 'color' => 'red')),
            new Document('4', array('price' => 30, 'color' => 'green')),
            new Document('5', array('price' => 40, 'color' => 'red')),
            new Document('6', array('price' => 35, 'color' => 'green')),
            new Document('7', array('price' => 42, 'color' => 'red')),
            new Document('8', array('price' => 41, 'color' => 'blue')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group functional
     */
    public function testHistogramAggregation() : void
    {
        $agg = new Histogram('hist', 'price', '10');
        $agg->setMinimumDocumentCount(0); // should return empty buckets

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('hist');

        $buckets = $results['buckets'];
        $this->assertEquals(5, count($buckets));
        $this->assertEquals(30, $buckets[3]['key']);
        $this->assertEquals(2, $buckets[3]['doc_count']);
    }
}
