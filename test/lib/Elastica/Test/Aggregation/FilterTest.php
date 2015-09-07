<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Avg;
use Elastica\Aggregation\Filter;
use Elastica\Document;
use Elastica\Filter\Range;
use Elastica\Filter\Term;
use Elastica\Query;

class FilterTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();

        $index->getType('test')->addDocuments(array(
            new Document('1', array('price' => 5, 'color' => 'blue')),
            new Document('2', array('price' => 8, 'color' => 'blue')),
            new Document('3', array('price' => 1, 'color' => 'red')),
            new Document('4', array('price' => 3, 'color' => 'green')),
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
            'filter' => array('range' => Map {'stock' => array('gt' => 0)}),
            'aggs' => array(
                'avg_price' => array('avg' => Map {'field' => 'price'}),
            ),
        );

        $agg = new Filter('in_stock_products');
        $agg->setFilter(new Range('stock', array('gt' => 0)));
        $avg = new Avg('avg_price');
        $avg->setField('price');
        $agg->addAggregation($avg);

        $this->assertEquals($expected, $agg->toArray());
    }

    /**
     * @group functional
     */
    public function testFilterAggregation() : void
    {
        $agg = new Filter('filter');
        $agg->setFilter(new Term(Map {'color' => 'blue'}));
        $avg = new Avg('price');
        $avg->setField('price');
        $agg->addAggregation($avg);

        $query = new Query();
        $query->addAggregation($agg);

        $results = $this->_getIndexForTest()->search($query)->getWaitHandle()->join()->getAggregation('filter');
        $results = $results['price']['value'];

        $this->assertEquals((5 + 8) / 2.0, $results);
    }

    /**
     * @group functional
     */
    public function testFilterNoSubAggregation() : void
    {
        $agg = new Avg('price');
        $agg->setField('price');

        $query = new Query();
        $query->addAggregation($agg);

        $results = $this->_getIndexForTest()->search($query)->getWaitHandle()->join()->getAggregation('price');
        $results = $results['value'];

        $this->assertEquals((5 + 8 + 1 + 3) / 4.0, $results);
    }

    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $agg = new Filter('foo', new Term(Map {'color' => 'blue'}));

        $expected = array(
            'filter' => array(
                'term' => Map {
                    'color' => 'blue',
                },
            ),
        );

        $this->assertEquals($expected, $agg->toArray());
    }

    /**
     * @group unit
     */
    public function testConstructWithoutFilter() : void
    {
        $agg = new Filter('foo');
        $this->assertEquals('foo', $agg->getName());
    }
}
