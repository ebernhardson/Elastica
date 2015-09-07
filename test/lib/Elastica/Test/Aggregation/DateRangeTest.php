<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\DateRange;
use Elastica\Document;
use Elastica\Query;
use Elastica\Type\Mapping;

class DateRangeTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->setMapping(new Mapping(null, array(
            'created' => array('type' => 'date'),
        )))->getWaitHandle()->join();

        $type->addDocuments(array(
            new Document('1', array('created' => 1390962135000)),
            new Document('2', array('created' => 1390965735000)),
            new Document('3', array('created' => 1390954935000)),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group functional
     */
    public function testDateRangeAggregation() : void
    {
        $agg = new DateRange('date');
        $agg->setField('created');
        $agg->addRange(1390958535000)->addRange(null, 1390958535000);

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('date');

        foreach ($results['buckets'] as $bucket) {
            if (array_key_exists('to', $bucket)) {
                $this->assertEquals(1, $bucket['doc_count']);
            } elseif (array_key_exists('from', $bucket)) {
                $this->assertEquals(2, $bucket['doc_count']);
            }
        }
    }

    /**
     * @group functional
     */
    public function testDateRangeSetFormat() : void
    {
        $agg = new DateRange('date');
        $agg->setField('created');
        $agg->addRange(1390958535000)->addRange(null, 1390958535000);
        $agg->setFormat('m-y-d');

        $query = new Query();
        $query->addAggregation($agg);

        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('date');
        $this->assertEquals('22-2014-29', $results['buckets'][0]['to_as_string']);
    }
}
