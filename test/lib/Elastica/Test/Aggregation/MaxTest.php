<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Max;
use Elastica\Document;
use Elastica\Query;
use Elastica\Script;

class MaxTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();

        $index->getType('test')->addDocuments(array(
            new Document('1', array('price' => 5)),
            new Document('2', array('price' => 8)),
            new Document('3', array('price' => 1)),
            new Document('4', array('price' => 3)),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $expected = array(
            'max' => Map {
                'field' => 'price',
                'script' => '_value * conversion_rate',
                'params' => Map {
                    'conversion_rate' => 1.2,
                },
            },
            'aggs' => array(
                'subagg' => array('max' => Map {'field' => 'foo'}),
            ),
        );

        $agg = new Max('min_price_in_euros');
        $agg->setField('price');
        $agg->setScript(new Script('_value * conversion_rate', Map {'conversion_rate' => 1.2}));
        $max = new Max('subagg');
        $max->setField('foo');
        $agg->addAggregation($max);

        $this->assertEquals($expected, $agg->toArray());
    }

    /**
     * @group functional
     */
    public function testMaxAggregation() : void
    {
        $index = $this->_getIndexForTest();

        $agg = new Max('min_price');
        $agg->setField('price');

        $query = new Query();
        $query->addAggregation($agg);
        $results = $index->search($query)->getWaitHandle()->join()->getAggregation('min_price');

        $this->assertEquals(8, $results['value']);

        // test using a script
        $agg->setScript(new Script('_value * conversion_rate', Map {'conversion_rate' => 1.2}));
        $query = new Query();
        $query->addAggregation($agg);
        $results = $index->search($query)->getWaitHandle()->join()->getAggregation('min_price');

        $this->assertEquals(8 * 1.2, $results['value']);
    }
}
