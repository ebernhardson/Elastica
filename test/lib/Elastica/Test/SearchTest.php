<?hh
namespace Elastica\Test;

use Elastica\Aggregation;
use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\FunctionScore;
use Elastica\Query\MatchAll;
use Elastica\Query\QueryString;
use Elastica\Script;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;

class SearchTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $this->assertInstanceOf('Elastica\Search', $search);
        $this->assertSame($client, $search->getClient());
    }

    /**
     * @group functional
     */
    public function testAddIndex() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $index1 = $this->_createIndex();
        $index2 = $this->_createIndex();

        $search->addIndex($index1);
        $indices = $search->getIndices();

        $this->assertEquals(1, count($indices));

        $search->addIndex($index2);
        $indices = $search->getIndices();

        $this->assertEquals(2, count($indices));

        $this->assertTrue(in_array($index1->getName(), $indices));
        $this->assertTrue(in_array($index2->getName(), $indices));

        // Add string
        $search->addIndex('test3');
        $indices = $search->getIndices();

        $this->assertEquals(3, count($indices));
        $this->assertTrue(in_array('test3', $indices));
    }

    /**
     * @group unit
     */
    public function testAddIndices() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $indices = array();
        $indices[] = $client->getIndex('elastica_test1');
        $indices[] = $client->getIndex('elastica_test2');

        $search->addIndices($indices);

        $this->assertEquals(2, count($search->getIndices()));
    }

    /**
     * @group functional
     */
    public function testAddType() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $index = $this->_createIndex();

        $type1 = $index->getType('type1');
        $type2 = $index->getType('type2');

        $this->assertEquals(array(), $search->getTypes());

        $search->addType($type1);
        $types = $search->getTypes();

        $this->assertEquals(1, count($types));

        $search->addType($type2);
        $types = $search->getTypes();

        $this->assertEquals(2, count($types));

        $this->assertTrue(in_array($type1->getName(), $types));
        $this->assertTrue(in_array($type2->getName(), $types));

        // Add string
        $search->addType('test3');
        $types = $search->getTypes();

        $this->assertEquals(3, count($types));
        $this->assertTrue(in_array('test3', $types));
    }

    /**
     * @group unit
     */
    public function testAddTypes() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $index = $client->getIndex('foo');

        $types = array();
        $types[] = $index->getType('type1');
        $types[] = $index->getType('type2');

        $search->addTypes($types);

        $this->assertEquals(2, count($search->getTypes()));
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testAddTypeInvalid() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $search->addType(new \stdClass());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testAddIndexInvalid() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $search->addIndex(new \stdClass());
    }

    /**
     * @group unit
     */
    public function testAddNumericIndex() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $search->addIndex(1);

        $this->assertContains('1', $search->getIndices(), 'Make sure it has been added and converted to string');
    }

    /**
     * @group functional
     */
    public function testGetPath() : void
    {
        $client = $this->_getClient();
        $search1 = new Search($client);
        $search2 = new Search($client);

        $index1 = $this->_createIndex();
        $index2 = $this->_createIndex();

        $type1 = $index1->getType('type1');
        $type2 = $index1->getType('type2');

        // No index
        $this->assertEquals('/_search', $search1->getPath());

        // Only index
        $search1->addIndex($index1);
        $this->assertEquals($index1->getName().'/_search', $search1->getPath());

        // MUltiple index, no types
        $search1->addIndex($index2);
        $this->assertEquals($index1->getName().','.$index2->getName().'/_search', $search1->getPath());

        // Single type, no index
        $search2->addType($type1);
        $this->assertEquals('_all/'.$type1->getName().'/_search', $search2->getPath());

        // Multiple types
        $search2->addType($type2);
        $this->assertEquals('_all/'.$type1->getName().','.$type2->getName().'/_search', $search2->getPath());

        // Combine index and types
        $search2->addIndex($index1);
        $this->assertEquals($index1->getName().'/'.$type1->getName().','.$type2->getName().'/_search', $search2->getPath());
    }

    /**
     * @group functional
     */
    public function testSearchRequest() : void
    {
        $client = $this->_getClient();
        $search1 = new Search($client);

        $index1 = $this->_createIndex();
        $index2 = $this->_createIndex();

        $type1 = $index1->getType('hello1');

        $result = $search1->search(array())->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());

        $search1->addIndex($index1);

        $result = $search1->search(array())->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());

        $search1->addIndex($index2);

        $result = $search1->search(array())->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());

        $search1->addType($type1);

        $result = $search1->search(array())->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());
    }

    /**
     * @group functional
     */
    public function testSearchScrollRequest() : void
    {
        $client = $this->_getClient();

        $index = $this->_createIndex();
        $type = $index->getType('scrolltest');

        $docs = array();
        for ($x = 1; $x <= 10; ++$x) {
            $docs[] = new Document((string) $x, array('id' => $x, 'testscroll' => 'jbafford'));
        }

        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search = new Search($client);
        $search->addIndex($index)->addType($type);
        $result = $search->search(array(), array(
            Search::OPTION_SEARCH_TYPE => Search::OPTION_SEARCH_TYPE_SCAN,
            Search::OPTION_SCROLL => '5m',
            Search::OPTION_SIZE => 5,
        ))->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());

        $scrollId = $result->getResponse()->getScrollId();
        $this->assertNotEmpty($scrollId);

        //There are 10 items, and we're scrolling with a size of 5
        //So we should get two results of 5 items, and then no items
        //We should also have sent the raw scroll_id as the HTTP request body
        $search = new Search($client);
        $result = $search->search(array(), array(
            Search::OPTION_SCROLL => '5m',
            Search::OPTION_SCROLL_ID => $scrollId,
        ))->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());
        $this->assertEquals(5, count($result->getResults()));
        $this->assertArrayNotHasKey(Search::OPTION_SCROLL_ID, $search->getClient()->getLastRequest()?->getQuery());
        $this->assertEquals($scrollId, $search->getClient()->getLastRequest()?->getData());

        $result = $search->search(array(), array(
            Search::OPTION_SCROLL => '5m',
            Search::OPTION_SCROLL_ID => $scrollId,
        ))->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());
        $this->assertEquals(5, count($result->getResults()));
        $this->assertArrayNotHasKey(Search::OPTION_SCROLL_ID, $search->getClient()->getLastRequest()?->getQuery());
        $this->assertEquals($scrollId, $search->getClient()->getLastRequest()?->getData());

        $result = $search->search(array(), array(
            Search::OPTION_SCROLL => '5m',
            Search::OPTION_SCROLL_ID => $scrollId,
        ))->getWaitHandle()->join();
        $this->assertFalse($result->getResponse()->hasError());
        $this->assertEquals(0, count($result->getResults()));
        $this->assertArrayNotHasKey(Search::OPTION_SCROLL_ID, $search->getClient()->getLastRequest()?->getQuery());
        $this->assertEquals($scrollId, $search->getClient()->getLastRequest()?->getData());
    }

    /**
     * Default Limit tests for \Elastica\Search.
     *
     * @group functional
     */
    public function testLimitDefaultSearch() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $index = $client->getIndex('zero');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();

        $type = $index->getType('zeroType');
        $type->addDocuments(array(
            new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('2', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('3', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('4', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('5', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('6', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('7', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('8', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('9', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('10', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('11', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search->addIndex($index)->addType($type);

        // default limit results  (default limit is 10)
        $resultSet = $search->search('farrelley')->getWaitHandle()->join();
        $this->assertEquals(10, $resultSet->count());

        // limit = 1
        $resultSet = $search->search('farrelley', 1)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testArrayConfigSearch() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $index = $client->getIndex('zero');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();

        $docs = array();
        for ($i = 0; $i < 11; ++$i) {
            $docs[] = new Document((string) $i, array('id' => 1, 'email' => 'test@test.com', 'username' => 'test'));
        }

        $type = $index->getType('zeroType');
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search->addIndex($index)->addType($type);
        //Backward compatibility, integer => limit
        // default limit results  (default limit is 10)
        $resultSet = $search->search('test')->getWaitHandle()->join();
        $this->assertEquals(10, $resultSet->count());

        // limit = 1
        $resultSet = $search->search('test', 1)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        //Array with limit
        $resultSet = $search->search('test', array('limit' => 2))->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        //Array with size
        $resultSet = $search->search('test', array('size' => 2))->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        //Array with from
        $resultSet = $search->search('test', array('from' => 10))->getWaitHandle()->join();
        $this->assertEquals(10, $resultSet->current()->getId());

        //Array with routing
        $resultSet = $search->search('test', array('routing' => 'r1,r2'))->getWaitHandle()->join();
        $this->assertEquals(10, $resultSet->count());

        //Array with limit and routing
        $resultSet = $search->search('test', array('limit' => 5, 'routing' => 'r1,r2'))->getWaitHandle()->join();
        $this->assertEquals(5, $resultSet->count());

        //Search types
        $resultSet = $search->search('test', array('limit' => 5, 'search_type' => 'count'))->getWaitHandle()->join();
        $this->assertTrue(($resultSet->count() === 0) && $resultSet->getTotalHits() === 11);

        //Timeout - this one is a bit more tricky to test
        $mockResponse = new \Elastica\Response(json_encode(array('timed_out' => true)));
        $client = $this->getMockBuilder('Elastica\\Client')
            ->disableOriginalConstructor()
            ->getMock();
        $client->method('request')
            ->will($this->returnValue($this->asAwaitable($mockResponse)));
        $search = new Search($client);
        $script = new Script('Thread.sleep(100); return _score;');
        $query = new FunctionScore();
        $query->addScriptScoreFunction($script);
        $resultSet = $search->search($query, array('timeout' => 50))->getWaitHandle()->join();
        $this->assertTrue($resultSet->hasTimedOut());

        // Throws InvalidException
        $resultSet = $search->search('test', array('invalid_option' => 'invalid_option_value'))->getWaitHandle()->join();
    }

	private async function asAwaitable(mixed $x) : Awaitable<mixed>
	{
		return $x;
	}

    /**
     * @group functional
     */
    public function testSearchWithVersionOption() : void
    {
        $index = $this->_createIndex();
        $doc = new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'ruflin'));
        $index->getType('test')->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search = new Search($index->getClient());
        $search->addIndex($index);

        // Version param should not be inside by default
        $results = $search->search(new MatchAll())->getWaitHandle()->join();
        $hit = $results->current();
        $this->assertEquals(array(), $hit->getParam('_version'));

        // Added version param to result
        $results = $search->search(new MatchAll(), array('version' => true))->getWaitHandle()->join();
        $hit = $results->current();
        $this->assertEquals(1, $hit->getParam('_version'));
    }

    /**
     * @group functional
     */
    public function testCountRequest() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $index = $client->getIndex('zero');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();

        $type = $index->getType('zeroType');
        $type->addDocuments(array(
            new Document('1',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('2',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('3',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('4',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('5',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('6',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'marley')),
            new Document('7',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'marley')),
            new Document('8',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'marley')),
            new Document('9',  array('id' => 1, 'email' => 'test@test.com', 'username' => 'marley')),
            new Document('10', array('id' => 1, 'email' => 'test@test.com', 'username' => 'marley')),
            new Document('11', array('id' => 1, 'email' => 'test@test.com', 'username' => 'marley')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search->addIndex($index)->addType($type);

        $count = $search->count('farrelley')->getWaitHandle()->join();
        $this->assertEquals(5, $count);

        $count = $search->count('marley')->getWaitHandle()->join();
        $this->assertEquals(6, $count);

        $count = $search->count()->getWaitHandle()->join();
        $this->assertEquals(6, $count, 'Uses previous query set');

        $count = $search->count(new MatchAll())->getWaitHandle()->join();
        $this->assertEquals(11, $count);

        $count = $search->count('bunny')->getWaitHandle()->join();
        $this->assertEquals(0, $count);
    }

    /**
     * @group functional
     */
    public function testEmptySearch() : void
    {
        $client = $this->_getClient();
        $search = new Search($client);

        $index = $client->getIndex('zero');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();
        $type = $index->getType('zeroType');
        $type->addDocuments(array(
            new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('2', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('3', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('4', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('5', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('6', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('7', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley')),
            new Document('8', array('id' => 1, 'email' => 'test@test.com', 'username' => 'bunny')),
            new Document('9', array('id' => 1, 'email' => 'test@test.com', 'username' => 'bunny')),
            new Document('10', array('id' => 1, 'email' => 'test@test.com', 'username' => 'bunny')),
            new Document('11', array('id' => 1, 'email' => 'test@test.com', 'username' => 'bunny')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search->addIndex($index)->addType($type);
        $resultSet = $search->search()->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\ResultSet', $resultSet);
        $this->assertCount(10, $resultSet);
        $this->assertEquals(11, $resultSet->getTotalHits());

        $query = new QueryString('bunny');
        $search->setQuery($query);

        $resultSet = $search->search()->getWaitHandle()->join();

        $this->assertCount(4, $resultSet);
        $this->assertEquals(4, $resultSet->getTotalHits());
        $source = $resultSet->current()->getSource();
        $this->assertEquals('bunny', $source['username']);
    }

    /**
     * @group functional
     */
    public function testCount() : void
    {
        $index = $this->_createIndex();
        $search = new Search($index->getClient());
        $type = $index->getType('test');

        $doc = new Document('1', array('id' => 1, 'username' => 'ruflin'));

        $type->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search->addIndex($index);
        $search->addType($type);

        $result1 = $search->count(new \Elastica\Query\MatchAll())->getWaitHandle()->join();
        $this->assertEquals(1, $result1);

        $result2 = $search->count(new \Elastica\Query\MatchAll(), true)->getWaitHandle()->join();
        $this->assertInstanceOf('\Elastica\ResultSet', $result2);
        if ($result2 instanceof \Elastica\ResultSet) {
            $this->assertEquals(1, $result2->getTotalHits());
        }
    }

    /**
     * @group functional
     */
    public function testScanAndScroll() : void
    {
        $search = new Search($this->_getClient());
        $this->assertInstanceOf('Elastica\ScanAndScroll', $search->scanAndScroll());
    }

    /**
     * @group functional
     */
    public function testIgnoreUnavailableOption() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('elastica_7086b4c2ee585bbb6740ece5ed7ece01');
        $query = new MatchAll();

        $search = new Search($client);
        $search->addIndex($index);

        $exception = null;
        try {
            $search->search($query)->getWaitHandle()->join();
        } catch (ResponseException $e) {
            $exception = $e;
        }
        $this->assertEquals('IndexMissingException', $exception?->getElasticsearchException()?->getExceptionName());

        $results = $search->search($query, array(Search::OPTION_SEARCH_IGNORE_UNAVAILABLE => true))->getWaitHandle()->join();
        $this->assertInstanceOf('\Elastica\ResultSet', $results);
    }

    /**
     * @group functional
     */
    public function testQueryCacheOption() : void
    {
        $client = $this->_getClient();

        $index = $client->getIndex('zero');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();
        $type = $index->getType('zeroType');
        $type->addDocuments(array(
            new Document('1', array('id' => 1, 'username' => 'farrelley')),
            new Document('2', array('id' => 2, 'username' => 'bunny')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $aggregation = new Aggregation\Terms('username');
        $aggregation->setField('username');

        $query = new Query();
        $query->addAggregation($aggregation);

        $search = new Search($client);
        $search->addIndex($index);
        $search->setQuery($query);
        $search->setOption(Search::OPTION_SEARCH_TYPE, Search::OPTION_SEARCH_TYPE_COUNT);
        $search->setOption(Search::OPTION_QUERY_CACHE, true);

        // before search query cache should be empty
        $statsData = $index->getStats()->getWaitHandle()->join()->getData();
        $queryCache = /* UNSAFE_EXPR */ $statsData['_all']['primaries']['query_cache'];

        $this->assertEquals(0, $queryCache['memory_size_in_bytes']);
        $this->assertEquals(0, $queryCache['evictions']);
        $this->assertEquals(0, $queryCache['hit_count']);
        $this->assertEquals(0, $queryCache['miss_count']);

        // first search should result in cache miss and save data to cache
        $search->search()->getWaitHandle()->join();
        $index->getStats()->getWaitHandle()->join()->refresh()->getWaitHandle()->join();
        $statsData = $index->getStats()->getWaitHandle()->join()->getData();
        $queryCache = /* UNSAFE_EXPR */ $statsData['_all']['primaries']['query_cache'];

        $this->assertNotEquals(0, $queryCache['memory_size_in_bytes']);
        $this->assertEquals(0, $queryCache['evictions']);
        $this->assertEquals(0, $queryCache['hit_count']);
        $this->assertEquals(1, $queryCache['miss_count']);

        // next search should result in cache hit
        $search->search()->getWaitHandle()->join();
        $index->getStats()->getWaitHandle()->join()->refresh()->getWaitHandle()->join();
        $statsData = $index->getStats()->getWaitHandle()->join()->getData();
        $queryCache = /* UNSAFE_EXPR */ $statsData['_all']['primaries']['query_cache'];

        $this->assertNotEquals(0, $queryCache['memory_size_in_bytes']);
        $this->assertEquals(0, $queryCache['evictions']);
        $this->assertEquals(1, $queryCache['hit_count']);
        $this->assertEquals(1, $queryCache['miss_count']);
    }
}
