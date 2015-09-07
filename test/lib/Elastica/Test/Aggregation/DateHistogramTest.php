<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\DateHistogram;
use Elastica\Document;
use Elastica\Query;
use Elastica\Type\Mapping;

class DateHistogramTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->setMapping(new Mapping(null, array(
            'created' => array('type' => 'date'),
        )))->getWaitHandle()->join();

        $type->addDocuments(array(
            new Document('1', array('created' => '2014-01-29T00:20:00')),
            new Document('2', array('created' => '2014-01-29T02:20:00')),
            new Document('3', array('created' => '2014-01-29T03:20:00')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group functional
     */
    public function testDateHistogramAggregation() : void
    {
        $agg = new DateHistogram('hist', 'created', '1h');

        $query = new Query();
        $query->addAggregation($agg);
        $response= $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('hist');

        $this->assertEquals(3, count($results['buckets']));
    }

    /**
     * @group unit
     */
    public function testSetOffset() : void
    {
        $agg = new DateHistogram('hist', 'created', '1h');

        $agg->setOffset('3m');

        $expected = array(
            'date_histogram' => Map {
                'field' => 'created',
                'interval' => '1h',
                'offset' => '3m',
            },
        );

        $this->assertEquals($expected, $agg->toArray());

        $this->assertInstanceOf('Elastica\Aggregation\DateHistogram', $agg->setOffset('3m'));
    }

    /**
     * @group functional
     */
    public function testSetOffsetWorks() : void
    {
        $agg = new DateHistogram('hist', 'created', '1m');
        $agg->setOffset('+40s');

        $query = new Query();
        $query->addAggregation($agg);
        $results = $this->_getIndexForTest()->search($query)->getWaitHandle()->join()->getAggregation('hist');

        $this->assertEquals('2014-01-29T00:19:40.000Z', $results['buckets'][0]['key_as_string']);
    }

    /**
     * @group unit
     */
    public function testSetTimezone() : void
    {
        $agg = new DateHistogram('hist', 'created', '1h');

        $agg->setTimezone('-02:30');

        $expected = array(
            'date_histogram' => Map {
                'field' => 'created',
                'interval' => '1h',
                'time_zone' => '-02:30',
            },
        );

        $this->assertEquals($expected, $agg->toArray());

        $this->assertInstanceOf('Elastica\Aggregation\DateHistogram', $agg->setTimezone('-02:30'));
    }
}
