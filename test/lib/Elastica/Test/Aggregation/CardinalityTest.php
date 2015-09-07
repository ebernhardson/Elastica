<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Cardinality;
use Elastica\Document;
use Elastica\Query;

class CardinalityTest extends BaseAggregationTest
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
    public function testCardinalityAggregation() : void
    {
        $agg = new Cardinality('cardinality');
        $agg->setField('color');

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('cardinality');

        $this->assertEquals(3, $results['value']);
    }

    /**
     * @dataProvider validPrecisionThresholdProvider
     * @group unit
     *
     * @param $threshold
     */
    public function testPrecisionThreshold($threshold) : void
    {
        $agg = new Cardinality('threshold');
        $agg->setPrecisionThreshold($threshold);

        $this->assertNotNull($agg->getParam('precision_threshold'));
        $this->assertInternalType('int', $agg->getParam('precision_threshold'));
    }

    public function validPrecisionThresholdProvider() : array<string, array<int>>
    {
        return array(
            'negative-int' => array(-140),
            'zero' => array(0),
            'positive-int' => array(150),
            'more-than-max' => array(40001),
        );
    }

    /**
     * @dataProvider validRehashProvider
     * @group unit
     *
     * @param bool $rehash
     */
    public function testRehash($rehash) : void
    {
        $agg = new Cardinality('rehash');
        $agg->setRehash($rehash);

        $this->assertNotNull($agg->getParam('rehash'));
        $this->assertInternalType('boolean', $agg->getParam('rehash'));
    }

    public function validRehashProvider() : array<string, array<bool>>
    {
        return array(
            'true' => array(true),
            'false' => array(false),
        );
    }
}
