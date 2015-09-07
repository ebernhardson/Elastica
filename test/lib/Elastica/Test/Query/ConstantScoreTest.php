<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Filter\Ids;
use Elastica\Filter\Term;
use Elastica\Index;
use Elastica\Query\ConstantScore;
use Elastica\Query\MatchAll;
use Elastica\Test\Base as BaseTest;

class ConstantScoreTest extends BaseTest
{
    public function dataProviderSampleQueries() : array<array>
    {
        return array(
            array(
                new Term(Map {'foo' => 'bar'}),
                array(
                    'constant_score' => array(
                        'filter' => array(
                            'term' => array(
                                'foo' => 'bar',
                            ),
                        ),
                    ),
                ),
            ),
            array(
                array(
                    'and' => array(
                        array(
                            'query' => array(
                                'query_string' => array(
                                    'query' => 'foo',
                                    'default_field' => 'something',
                                ),
                            ),
                        ),
                        array(
                            'query' => array(
                                'query_string' => array(
                                    'query' => 'bar',
                                    'default_field' => 'something',
                                ),
                            ),
                        ),
                    ),
                ),
                '{"constant_score":{"filter":{"and":[{"query":{"query_string":{"query":"foo","default_field":"something"}}},{"query":{"query_string":{"query":"bar","default_field":"something"}}}]}}}',
            ),
        );
    }
    /**
     * @group unit
     * @dataProvider dataProviderSampleQueries
     */
    public function testSimple($filter, $expected) : void
    {
        $query = new ConstantScore();
        $query->setFilter($filter);
        if (is_string($expected)) {
            $expected = json_decode($expected, true);
        }
        $this->assertEquals($expected, json_decode(json_encode($query->toArray()), true));
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new ConstantScore();

        $boost = 1.2;
        $filter = new Ids();
        $filter->setIds(array(1));

        $query->setFilter($filter);
        $query->setBoost($boost);

        $expectedArray = array(
            'constant_score' => Map {
                'filter' => $filter->toArray(),
                'boost' => $boost,
            },
        );

        $this->assertEquals($expectedArray, $query->toArray());
    }

    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $filter = new Ids();
        $filter->setIds(array(1));

        $query = new ConstantScore($filter);

        $expectedArray = array(
            'constant_score' => Map {
                'filter' => $filter->toArray(),
            },
        );

        $this->assertEquals($expectedArray, $query->toArray());
    }

    /**
     * @group functional
     */
    public function testQuery() : void
    {
        $index = $this->_createIndex();

        $type = $index->getType('constant_score');
        $type->addDocuments(array(
            new Document('1', array('id' => 1, 'email' => 'hans@test.com', 'username' => 'hans')),
            new Document('2', array('id' => 2, 'email' => 'emil@test.com', 'username' => 'emil')),
            new Document('3', array('id' => 3, 'email' => 'ruth@test.com', 'username' => 'ruth')),
        ))->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $boost = 1.3;
        $query_match = new MatchAll();

        $query = new ConstantScore();
        $query->setQuery($query_match);
        $query->setBoost($boost);

        $expectedArray = array(
            'constant_score' => Map {
                'query' => $query_match->toArray(),
                'boost' => $boost,
            },
        );

        $this->assertEquals($expectedArray, $query->toArray());
        $resultSet = $type->search($query)->getWaitHandle()->join();

        $results = $resultSet->getResults();

        $this->assertEquals($resultSet->count(), 3);
        $this->assertEquals($results[1]->getScore(), 1);
    }

    /**
     * @group unit
     */
    public function testConstructEmpty() : void
    {
        $query = new ConstantScore();
        $expectedArray = array('constant_score' => Map {});

        $this->assertEquals($expectedArray, $query->toArray());
    }
}
