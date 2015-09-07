<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\Avg;
use Elastica\Aggregation\Filter;
use Elastica\Aggregation\Filters;
use Elastica\Document;
use Elastica\Filter\Term;
use Elastica\Index;
use Elastica\Query;

class FiltersTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : Index
    {
        $index = $this->_createIndex('filter');

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
    public function testToArrayUsingNamedFilters() : void
    {
        $expected = array(
            'filters' => array(
                'filters' => array(
                    'blue' => array(
                        'term' => Map {'color' => 'blue'},
                    ),
                    'red' => array(
                        'term' => Map {'color' => 'red'},
                    ),
                ),
            ),
            'aggs' => array(
                'avg_price' => array('avg' => Map {'field' => 'price'}),
            ),
        );

        $agg = new Filters('by_color');
        $agg->addFilter(new Term(Map {'color' => 'blue'}), 'blue');
        $agg->addFilter(new Term(Map {'color' => 'red'}), 'red');

        $avg = new Avg('avg_price');
        $avg->setField('price');
        $agg->addAggregation($avg);

        $this->assertEquals($expected, $agg->toArray());
    }

    /**
     * @group unit
     */
    public function testToArrayUsingAnonymousFilters() : void
    {
        $expected = array(
            'filters' => array(
                'filters' => array(
                    array(
                        'term' => array('color' => 'blue'),
                    ),
                    array(
                        'term' => array('color' => 'red'),
                    ),
                ),
            ),
            'aggs' => array(
                'avg_price' => array('avg' => Map {'field' => 'price'}),
            ),
        );

        $agg = new Filters('by_color');
        $agg->addFilter(new Term(Map {'color' => 'blue'}));
        $agg->addFilter(new Term(Map {'color' => 'red'}));

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
        $agg = new Filters('by_color');
        $agg->addFilter(new Term(Map {'color' => 'blue'}), 'blue');
        $agg->addFilter(new Term(Map {'color' => 'red'}), 'red');

        $avg = new Avg('avg_price');
        $avg->setField('price');
        $agg->addAggregation($avg);

        $query = new Query();
        $query->addAggregation($agg);

        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('by_color');

        $resultsForBlue = $results['buckets']['blue'];
        $resultsForRed = $results['buckets']['red'];

        $this->assertEquals(2, $resultsForBlue['doc_count']);
        $this->assertEquals(1, $resultsForRed['doc_count']);

        $this->assertEquals((5 + 8) / 2, $resultsForBlue['avg_price']['value']);
        $this->assertEquals(1, $resultsForRed['avg_price']['value']);
    }
}
