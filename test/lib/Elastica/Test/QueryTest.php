<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Exception\InvalidException;
use Elastica\Facet\Terms;
use Elastica\Query;
use Elastica\Query\Builder;
use Elastica\Query\Term;
use Elastica\Query\Text;
use Elastica\Script;
use Elastica\ScriptFields;
use Elastica\Suggest;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;

class QueryTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testStringConversion() : void
    {
        $queryString = '{
            "query" : {
                "filtered" : {
                "filter" : {
                    "range" : {
                    "due" : {
                        "gte" : "2011-07-18 00:00:00",
                        "lt" : "2011-07-25 00:00:00"
                    }
                    }
                },
                "query" : {
                    "text_phrase" : {
                    "title" : "Call back request"
                    }
                }
                }
            },
            "sort" : {
                "due" : {
                "reverse" : true
                }
            },
            "fields" : [
                "created", "assigned_to"
            ]
            }';

        $query = new Builder($queryString);
        $queryArray = $query->toArray();

        $this->assertInternalType('array', $queryArray);

        $this->assertEquals('2011-07-18 00:00:00', /* UNSAFE_EXPR */ $queryArray['query']['filtered']['filter']['range']['due']['gte']);
    }

    /**
     * @group unit
     */
    public function testRawQuery() : void
    {
        $textQuery = new Term(Map {'title' => 'test'});

        $query1 = Query::create($textQuery);

        $query2 = new Query();
        $query2->setRawQuery(Map {'query' => array('term' => Map {'title' => 'test'})});

        $this->assertEquals($query1->toArray(), $query2->toArray());
    }

    /**
     * @group unit
     */
    public function testSuggestShouldNotRemoveOtherParameters() : void
    {
        $query1 = new Query();
        $query2 = new Query();

        $suggest = new Suggest();
        $suggest->setGlobalText('test');

        $query1->setSize(40);
        $query1->setSuggest($suggest);

        $query2->setSuggest($suggest);
        $query2->setSize(40);

        $this->assertEquals($query1->toArray(), $query2->toArray());
    }

    /**
     * @group unit
     */
    public function testSetSuggestMustReturnQueryInstance() : void
    {
        $query = new Query();
        $suggest = new Suggest();
        $this->assertInstanceOf('Elastica\Query', $query->setSuggest($suggest));
    }

    /**
     * @group unit
     */
    public function testArrayQuery() : void
    {
        $query = Map {
            'query' => Map {
                'text' => Map {
                    'title' => 'test',
                },
            },
        };

        $query1 = Query::create($query);

        $query2 = new Query();
        $query2->setRawQuery(Map {'query' => Map {'text' => Map {'title' => 'test'}}});

        $this->assertEquals($query1->toArray(), $query2->toArray());
    }

    /**
     * @group functional
     */
    public function testSetSort() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->addDocuments(array(
            new Document('1', array('name' => 'hello world')),
            new Document('2', array('firstname' => 'guschti', 'lastname' => 'ruflin')),
            new Document('3', array('firstname' => 'nicolas', 'lastname' => 'ruflin')),
        ))->getWaitHandle()->join();

        $queryTerm = new Term();
        $queryTerm->setTerm('lastname', 'ruflin');

        $index->refresh()->getWaitHandle()->join();

        $query = Query::create($queryTerm);

        // ASC order
        $query->setSort(array(array('firstname' => array('order' => 'asc'))));
        $resultSet = $type->search($query)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        $first = $resultSet->current()->getData();
        $resultSet->next();
        $second = $resultSet->current()->getData();

        $this->assertEquals('guschti', $first['firstname']);
        $this->assertEquals('nicolas', $second['firstname']);

        // DESC order
        $query->setSort(array('firstname' => array('order' => 'desc')));
        $resultSet = $type->search($query)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        $first = $resultSet->current()->getData();
        $resultSet->next();
        $second = $resultSet->current()->getData();

        $this->assertEquals('nicolas', $first['firstname']);
        $this->assertEquals('guschti', $second['firstname']);
    }

    /**
     * @group unit
     */
    public function testAddSort() : void
    {
        $query = new Query();
        $sortParam = array('firstname' => array('order' => 'asc'));
        $query->addSort($sortParam);

        $this->assertEquals($query->getParam('sort'), array($sortParam));
    }

    /**
     * @group unit
     */
    public function testSetRawQuery() : void
    {
        $query = new Query();

        $params = Map {'query' => 'test'};
        $query->setRawQuery($params);

        $this->assertEquals($params, $query->toArray());
    }

    /**
     * @group unit
     */
    public function testSetFields() : void
    {
        $query = new Query();

        $params = array('query' => 'test');

        $query->setFields(array('firstname', 'lastname'));

        $data = $query->toArray();

        $this->assertContains('firstname', $data['fields']);
        $this->assertContains('lastname', $data['fields']);
        $this->assertCount(2, $data['fields']);
    }

    /**
     * @group unit
     */
    public function testGetQuery() : void
    {
        $query = new Query();

        try {
            $query->getQuery();
            $this->fail('should throw exception because query does not exist');
        } catch (InvalidException $e) {
            $this->assertTrue(true);
        }

        $termQuery = new Term();
        $termQuery->setTerm('text', 'value');
        $query->setQuery($termQuery);

        $this->assertSame($termQuery, $query->getQuery());
    }

    /**
     * @group unit
     */
    public function testSetFacets() : void
    {
        $query = new Query();

        $facet = new Terms('text');
        $query->setFacets(array($facet));

        $data = $query->toArray();

		$this->assertTrue(/* UNSAFE_EXPR */ $data->contains('facets'));
        $this->assertEquals(array('text' => array('terms' => Map {})), $data['facets']);

        $query->setFacets(array());

        $this->assertFalse(/* UNSAFE_EXPR */ $query->toArray()->contains('facets'));
    }

    /**
     * @group unit
     */
    public function testSetQueryToArrayCast() : void
    {
        $query = new Query();
        $termQuery = new Term();
        $termQuery->setTerm('text', 'value');
        $query->setQuery($termQuery);

        $termQuery->setTerm('text', 'another value');

        $anotherQuery = new Query();
        $anotherQuery->setQuery($termQuery);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group unit
     */
    public function testSetQueryToArrayChangeQuery() : void
    {
        $query = new Query();
        $termQuery = new Term();
        $termQuery->setTerm('text', 'value');
        $query->setQuery($termQuery);

        $queryArray = $query->toArray();

        $termQuery = $query->getQuery();
        $this->assertInstanceOf( 'Elastica\Query\Term', $termQuery );
        /* UNSAFE_EXPR */
        $termQuery->setTerm('text', 'another value');

        $this->assertNotEquals($queryArray, $query->toArray());
    }

    /**
     * @group unit
     */
    public function testSetScriptFieldsToArrayCast() : void
    {
        $query = new Query();
        $scriptFields = new ScriptFields();
        $scriptFields->addScript('script',  new Script('script'));

        $query->setScriptFields($scriptFields);

        $scriptFields->addScript('another script',  new Script('another script'));

        $anotherQuery = new Query();
        $anotherQuery->setScriptFields($scriptFields);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group unit
     */
    public function testAddScriptFieldsToArrayCast() : void
    {
        $query = new Query();
        $scriptField = new Script('script');

        $query->addScriptField('script', $scriptField);

        $scriptField->setScript('another script');

        $anotherQuery = new Query();
        $anotherQuery->addScriptField('script', $scriptField);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group unit
     */
    public function testAddFacetToArrayCast() : void
    {
        $query = new Query();
        $facet = new Terms('text');

        $query->addFacet($facet);

        $facet->setName('another text');

        $anotherQuery = new Query();
        $anotherQuery->addFacet($facet);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group unit
     */
    public function testAddAggregationToArrayCast() : void
    {
        $query = new Query();
        $aggregation = new \Elastica\Aggregation\Terms('text');

        $query->addAggregation($aggregation);

        $aggregation->setName('another text');

        $anotherQuery = new Query();
        $anotherQuery->addAggregation($aggregation);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group unit
     */
    public function testSetSuggestToArrayCast() : void
    {
        $query = new Query();
        $suggest = new Suggest();
        $suggest->setGlobalText('text');

        $query->setSuggest($suggest);

        $suggest->setGlobalText('another text');

        $anotherQuery = new Query();
        $anotherQuery->setSuggest($suggest);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group unit
     */
    public function testSetRescoreToArrayCast() : void
    {
        $query = new Query();
        $rescore = new \Elastica\Rescore\Query();
        $rescore->setQueryWeight(1.0);

        $query->setRescore($rescore);

        $rescore->setQueryWeight(2.0);

        $anotherQuery = new Query();
        $anotherQuery->setRescore($rescore);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group unit
     */
    public function testSetPostFilterToArrayCast() : void
    {
        $query = new Query();
        $postFilter = new \Elastica\Filter\Terms();
        $postFilter->setTerms('key', array('term'));
        $query->setPostFilter($postFilter);

        $postFilter->setTerms('another key', array('another term'));

        $anotherQuery = new Query();
        $anotherQuery->setPostFilter($postFilter);

        $this->assertEquals($query->toArray(), $anotherQuery->toArray());
    }

    /**
     * @group functional
     */
    public function testNoSource() : void
    {
        $index = $this->_createIndex();

        $type = new Type($index, 'user');

        // Adds 1 document to the index
        $doc1 = new Document('1',
            array('username' => 'ruflin', 'test' => array('2', '3', '5'))
        );
        $type->addDocument($doc1)->getWaitHandle()->join();

        // To update index
        $index->refresh()->getWaitHandle()->join();

        $query = Query::create('ruflin');
        $resultSet = $type->search($query)->getWaitHandle()->join();

        // Disable source
        $query->setSource(false);

        $resultSetNoSource = $type->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
        $this->assertEquals(1, $resultSetNoSource->count());

        // Tests if no source is in response except id
        $result = $resultSetNoSource->current();
        $this->assertEquals(1, $result->getId());
        $this->assertEmpty($result->getData());

        // Tests if source is in response except id
        $result = $resultSet->current();
        $this->assertEquals(1, $result->getId());
        $this->assertNotEmpty($result->getData());
    }
}
