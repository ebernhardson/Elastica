<?hh
namespace Elastica\Test\Query;

use Elastica\Query;
use Elastica\Query\Match;
use Elastica\Query\Term;
use Elastica\Rescore\Query as QueryRescore;
use Elastica\Test\Base as BaseTest;

class RescoreTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $query = new Query();
        $mainQuery = new Match();
        $mainQuery = $mainQuery->setFieldQuery('test1', 'foo');
        $secQuery = new Term();
        $secQuery = $secQuery->setTerm('test2', 'bar', 2.0);
        $queryRescore = new QueryRescore($secQuery);
        $query->setQuery($mainQuery);
        $query->setRescore($queryRescore);
        $data = $query->toArray();

        $expected = Map {
            'query' => array(
                'match' => Map {
                    'test1' => array(
                        'query' => 'foo',
                    ),
                },
            ),
            'rescore' => Map {
                'query' => array(
                    'rescore_query' => array(
                        'term' => Map {
                            'test2' => Map {
                                'value' => 'bar',
                                'boost' => 2,
                            },
                        },
                    ),
                ),
            },
        };

        $this->assertEquals($expected, $data);
    }

    /**
     * @group unit
     */
    public function testSetSize() : void
    {
        $query = new Query();
        $mainQuery = new Match();
        $mainQuery = $mainQuery->setFieldQuery('test1', 'foo');
        $secQuery = new Term();
        $secQuery = $secQuery->setTerm('test2', 'bar', 2.0);
        $queryRescore = new QueryRescore($secQuery);
        $queryRescore->setWindowSize(50);
        $query->setQuery($mainQuery);
        $query->setRescore($queryRescore);
        $data = $query->toArray();

        $expected = Map {
            'query' => array(
                'match' => Map {
                    'test1' => array(
                        'query' => 'foo',
                    ),
                },
            ),
            'rescore' => Map {
                'window_size' => 50,
                'query' => array(
                    'rescore_query' => array(
                        'term' => Map {
                            'test2' => Map {
                                'value' => 'bar',
                                'boost' => 2,
                            },
                        },
                    ),
                ),
            },
        };

        $this->assertEquals($expected, $data);
    }

    /**
     * @group unit
     */
    public function testSetWeights() : void
    {
        $query = new Query();
        $mainQuery = new Match();
        $mainQuery = $mainQuery->setFieldQuery('test1', 'foo');
        $secQuery = new Term();
        $secQuery = $secQuery->setTerm('test2', 'bar', 2.0);
        $queryRescore = new QueryRescore($secQuery);
        $queryRescore->setWindowSize(50);
        $queryRescore->setQueryWeight(.7);
        $queryRescore->setRescoreQueryWeight(1.2);
        $query->setQuery($mainQuery);
        $query->setRescore($queryRescore);
        $data = $query->toArray();

        $expected = Map {
            'query' => array(
                'match' => Map {
                    'test1' => array(
                        'query' => 'foo',
                    ),
                },
            ),
            'rescore' => Map {
                'window_size' => 50,
                'query' => array(
                    'rescore_query' => array(
                        'term' => Map {
                            'test2' => Map {
                                'value' => 'bar',
                                'boost' => 2,
                            },
                        },
                    ),
                    'query_weight' => 0.7,
                    'rescore_query_weight' => 1.2,
                ),
            },
        };

        $this->assertEquals($expected, $data);
    }

    /**
     * @group functional
     */
    public function testMultipleQueries() : void
    {
        $query = new Query();
        $mainQuery = new Match();
        $mainQuery = $mainQuery->setFieldQuery('test1', 'foo');

        $secQuery1 = new Term();
        $secQuery1 = $secQuery1->setTerm('test2', 'bar', 1.0);
        $rescoreQuery1 = new QueryRescore();
        $rescoreQuery1->setRescoreQuery($secQuery1);

        $secQuery2 = new Term();
        $secQuery2 = $secQuery2->setTerm('test2', 'tom', 2.0);
        $rescoreQuery2 = new QueryRescore();
        $rescoreQuery2->setRescoreQuery($secQuery2);

        $query->setQuery($mainQuery);
        $query->setRescore(array($rescoreQuery1, $rescoreQuery2));
        $data = $query->toArray();

        $expected = Map {
            'query' => array(
                'match' => Map {
                    'test1' => array(
                        'query' => 'foo',
                    ),
                },
            ),
            'rescore' => array(
                Map {
                    'query' => array(
                        'rescore_query' => array(
                            'term' => Map {
                                'test2' => Map {
                                    'value' => 'bar',
                                    'boost' => 1,
                                },
                            },
                        ),
                    ),
                },
                Map {
                    'query' => array(
                        'rescore_query' => array(
                            'term' => Map {
                                'test2' => Map {
                                    'value' => 'tom',
                                    'boost' => 2,
                                },
                            },
                        ),
                    ),
                },
            ),
        };

        $this->assertEquals($expected, $data);

        $index = $this->_createIndex();
        $index->refresh()->getWaitHandle()->join();
		
		usleep(5000); // FIXME intermittant failure without this sleep
        $results = $index->search($query)->getWaitHandle()->join();
        $response = $results->getResponse();

        $this->assertEquals(true, $response->isOk());
        $this->assertEquals(0, $results->getTotalHits());
    }

    /**
     * @group functional
     */
    public function testQuery() : void
    {
        $query = new Query();
        $mainQuery = new Match();
        $mainQuery = $mainQuery->setFieldQuery('test1', 'foo');
        $secQuery = new Term();
        $secQuery = $secQuery->setTerm('test2', 'bar', 2.0);
        $queryRescore = new QueryRescore($secQuery);
        $queryRescore->setWindowSize(50);
        $queryRescore->setQueryWeight(.7);
        $queryRescore->setRescoreQueryWeight(1.2);
        $query->setQuery($mainQuery);
        $query->setRescore($queryRescore);
        $data = $query->toArray();

        $index = $this->_createIndex();
        $index->refresh()->getWaitHandle()->join();

		usleep(5000); // FIXME intermittant failure without this sleep
        $results = $index->search($query)->getWaitHandle()->join();
        $response = $results->getResponse();

        $this->assertEquals(true, $response->isOk());
        $this->assertEquals(0, $results->getTotalHits());
    }
}
