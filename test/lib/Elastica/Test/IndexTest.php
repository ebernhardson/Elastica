<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Index;
use Elastica\Query\HasChild;
use Elastica\Query\QueryString;
use Elastica\Query\SimpleQueryString;
use Elastica\Query\Term;
use Elastica\Status;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;
use Elastica\Type\Mapping;

class IndexTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testMapping() : void
    {
        $index = $this->_createIndex();
        $doc = new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'hanswurst', 'test' => array('2', '3', '5')));

        $type = $index->getType('test');

        $mapping = array('id' => array('type' => 'integer', 'store' => true), 'email' => array('type' => 'string', 'store' => 'no'),
            'username' => array('type' => 'string', 'store' => 'no'), 'test' => array('type' => 'integer', 'store' => 'no'),);
        $type->setMapping($mapping)->getWaitHandle()->join();

        $type->addDocument($doc)->getWaitHandle()->join();
        $index->optimize()->getWaitHandle()->join();

        $storedMapping = $index->getMapping()->getWaitHandle()->join();

        $this->assertEquals($storedMapping['test']['properties']['id']['type'], 'integer');
        $this->assertEquals($storedMapping['test']['properties']['id']['store'], true);
        $this->assertEquals($storedMapping['test']['properties']['email']['type'], 'string');
        $this->assertEquals($storedMapping['test']['properties']['username']['type'], 'string');
        $this->assertEquals($storedMapping['test']['properties']['test']['type'], 'integer');

        $result = $type->search('hanswurst')->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testGetMappingAlias() : void
    {
        $index = $this->_createIndex();
        $indexName = $index->getName();

        $aliasName = 'test-mapping-alias';
        $index->addAlias($aliasName)->getWaitHandle()->join();

        $type = new Type($index, 'test');
        $mapping = new Mapping($type, array(
                'id' => array('type' => 'integer', 'store' => 'yes'),
            ));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $client = $index->getClient();

        // Index mapping
        $mapping1 = $client->getIndex($indexName)->getMapping()->getWaitHandle()->join();

        // Alias mapping
        $mapping2 = $client->getIndex($aliasName)->getMapping()->getWaitHandle()->join();

        // Make sure, a mapping is set
        $this->assertNotEmpty($mapping1);

        // Alias and index mapping should be identical
        $this->assertEquals($mapping1, $mapping2);
    }

    /**
     * @group functional
     */
    public function testParent() : void
    {
        $index = $this->_createIndex();

        $typeBlog = new Type($index, 'blog');

        $typeComment = new Type($index, 'comment');

        $mapping = new Mapping();
        $mapping->setParam('_parent', array('type' => 'blog'));
        $typeComment->setMapping($mapping)->getWaitHandle()->join();

        $entry1 = new Document('1');
        $entry1->set('title', 'Hello world');
        $typeBlog->addDocument($entry1)->getWaitHandle()->join();

        $entry2 = new Document('2');
        $entry2->set('title', 'Foo bar');
        $typeBlog->addDocument($entry2)->getWaitHandle()->join();

        $entry3 = new Document('3');
        $entry3->set('title', 'Till dawn');
        $typeBlog->addDocument($entry3)->getWaitHandle()->join();

        $comment = new Document('1');
        $comment->set('author', 'Max');
        $comment->setParent(2); // Entry Foo bar
        $typeComment->addDocument($comment)->getWaitHandle()->join();

        $index->optimize()->getWaitHandle()->join();

        $query = new HasChild('Max', 'comment');
        $resultSet = $typeBlog->search($query)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
        $this->assertEquals(array('title' => 'Foo bar'), $resultSet->current()->getData());
    }

    /**
     * @group functional
     */
    public function testAddPdfFile() : void
    {
        $this->_checkAttachmentsPlugin();
        $indexMapping = array('file' => array('type' => 'attachment', 'store' => 'no'), 'text' => array('type' => 'string', 'store' => 'no'));

        $indexParams = array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0));

        $index = $this->_createIndex();
        $type = new Type($index, 'test');

        $index->create($indexParams, true)->getWaitHandle()->join();
        $type->setMapping($indexMapping)->getWaitHandle()->join();

        $doc1 = new Document('1');
        $doc1->addFile('file', BASE_PATH.'/data/test.pdf', 'application/pdf');
        $doc1->set('text', 'basel world');
        $type->addDocument($doc1)->getWaitHandle()->join();

        $doc2 = new Document('2');
        $doc2->set('text', 'running in basel');
        $type->addDocument($doc2)->getWaitHandle()->join();

        $index->optimize()->getWaitHandle()->join();

        $resultSet = $type->search('xodoa')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $resultSet = $type->search('basel')->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        // Author is ruflin
        $resultSet = $type->search('ruflin')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        // String does not exist in file
        $resultSet = $type->search('guschti')->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testAddPdfFileContent() : void
    {
        $this->_checkAttachmentsPlugin();
        $indexMapping = array('file' => array('type' => 'attachment', 'store' => 'no'), 'text' => array('type' => 'string', 'store' => 'no'));

        $indexParams = array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0));

        $index = $this->_createIndex();
        $type = new Type($index, 'test');

        $index->create($indexParams, true)->getWaitHandle()->join();
        $type->setMapping($indexMapping)->getWaitHandle()->join();

        $doc1 = new Document('1');
        $doc1->addFileContent('file', file_get_contents(BASE_PATH.'/data/test.pdf'));
        $doc1->set('text', 'basel world');
        $type->addDocument($doc1)->getWaitHandle()->join();

        $doc2 = new Document('2');
        $doc2->set('text', 'running in basel');
        $type->addDocument($doc2)->getWaitHandle()->join();

        $index->optimize()->getWaitHandle()->join();

        $resultSet = $type->search('xodoa')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $resultSet = $type->search('basel')->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        // Author is ruflin
        $resultSet = $type->search('ruflin')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        // String does not exist in file
        $resultSet = $type->search('guschti')->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testAddWordxFile() : void
    {
        $this->_checkAttachmentsPlugin();
        $indexMapping = array('file' => array('type' => 'attachment'), 'text' => array('type' => 'string', 'store' => 'no'));

        $indexParams = array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0));

        $index = $this->_createIndex();
        $type = new Type($index, 'content');

        $index->create($indexParams, true)->getWaitHandle()->join();
        $type->setMapping($indexMapping)->getWaitHandle()->join();

        $doc1 = new Document('1');
        $doc1->addFile('file', BASE_PATH.'/data/test.docx');
        $doc1->set('text', 'basel world');
        $type->addDocument($doc1)->getWaitHandle()->join();

        $index->optimize()->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $doc2 = new Document('2');
        $doc2->set('text', 'running in basel');
        $type->addDocument($doc2)->getWaitHandle()->join();

        $index->optimize()->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('basel')->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());

        $resultSet = $type->search('ruflin')->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());

        $resultSet = $type->search('Xodoa')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testExcludeFileSource() : void
    {
        $this->_checkAttachmentsPlugin();
        $indexMapping = array('file' => array('type' => 'attachment', 'store' => 'yes'), 'text' => array('type' => 'string', 'store' => 'yes'),
            'title' => array('type' => 'string', 'store' => 'yes'),);

        $indexParams = array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0));

        $index = $this->_createIndex();
        $type = new Type($index, 'content');

        $mapping = Mapping::create($indexMapping);
        $mapping->setSource(array('excludes' => array('file')));

        $mapping->setType($type);

        $index->create($indexParams, true)->getWaitHandle()->join();
        $type->setMapping($mapping)->getWaitHandle()->join();

        $docId = '1';
        $text = 'Basel World';
        $title = 'No Title';

        $doc1 = new Document($docId);
        $doc1->addFile('file', BASE_PATH.'/data/test.docx');
        $doc1->set('text', $text);
        $doc1->set('title', $title);
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Optimization necessary, as otherwise source still in realtime get
        $index->optimize()->getWaitHandle()->join();

        $data = $type->getDocument($docId)->getWaitHandle()->join()->getData();
        $this->assertEquals(/* UNSAFE_EXPR */ $data['title'], $title);
        $this->assertEquals(/* UNSAFE_EXPR */ $data['text'], $text);
        $this->assertFalse(isset(/* UNSAFE_EXPR */ $data['file']));
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\ResponseException
     */
    public function testAddRemoveAlias() : void
    {
        $client = $this->_getClient();

        $indexName1 = 'test1';
        $aliasName = 'test-alias';
        $typeName = 'test';

        $index = $client->getIndex($indexName1);
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();

        $doc = new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'ruflin'));

        $type = $index->getType($typeName);
        $type->addDocument($doc)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('ruflin')->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());

        $data = $index->addAlias($aliasName, true)->getWaitHandle()->join()->getData();
        $this->assertTrue(/* UNSAFE_EXPR */ $data['acknowledged']);

        $index2 = $client->getIndex($aliasName);
        $type2 = $index2->getType($typeName);

        $resultSet2 = $type2->search('ruflin')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet2->count());

        $response = $index->removeAlias($aliasName)->getWaitHandle()->join()->getData();
        $this->assertTrue(/* UNSAFE_EXPR */ $response['acknowledged']);

        $client->getIndex($aliasName)->getType($typeName)->search('ruflin')->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testCount() : void
    {
        $index = $this->_createIndex();

        // Add document to normal index
        $doc1 = new Document(null, array('name' => 'ruflin'));
        $doc2 = new Document(null, array('name' => 'nicolas'));

        $type = $index->getType('test');
        $type->addDocument($doc1)->getWaitHandle()->join();
        $type->addDocument($doc2)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $this->assertEquals(2, $index->count()->getWaitHandle()->join());

        $query = new Term();
        $key = 'name';
        $value = 'nicolas';
        $query->setTerm($key, $value);

        $this->assertEquals(1, $index->count($query)->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testDeleteByQueryWithQueryString() : void
    {
        $index = $this->_createIndex();
        $type1 = new Type($index, 'test1');
        $type1->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type1->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $type2 = new Type($index, 'test2');
        $type2->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type2->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(4, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        // Delete first document
        $response = $index->deleteByQuery('nicolas')->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        // Makes sure, document is deleted
        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(0, $response->count());
    }

    /**
     * @group functional
     */
    public function testDeleteByQueryWithQuery() : void
    {
        $index = $this->_createIndex();
        $type1 = new Type($index, 'test1');
        $type1->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type1->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $type2 = new Type($index, 'test2');
        $type2->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type2->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(4, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        // Delete first document
        $response = $index->deleteByQuery(new SimpleQueryString('nicolas'))->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        // Makes sure, document is deleted
        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(0, $response->count());
    }

    /**
     * @group functional
     */
    public function testDeleteByQueryWithQueryAndOptions() : void
    {
        $index = $this->_createIndex(null, true, 2);
        $type1 = new Type($index, 'test1');
        $type1->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type1->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $type2 = new Type($index, 'test2');
        $type2->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type2->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(4, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        // Route to the wrong document id; should not delete
        $response = $index->deleteByQuery(new SimpleQueryString('nicolas'), array('routing' => '2'))->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(4, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        // Delete first document
        $response = $index->deleteByQuery(new SimpleQueryString('nicolas'), array('routing' => '1'))->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        // Makes sure, document is deleted
        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(0, $response->count());
    }

    /**
     * @group functional
     */
    public function testDeleteIndexDeleteAlias() : void
    {
        $indexName = 'test';
        $aliasName = 'test-aliase';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);

        $index->create(array(), true)->getWaitHandle()->join();
        $index->addAlias($aliasName)->getWaitHandle()->join();

        $status = Status::create($client)->getWaitHandle()->join();
        $this->assertTrue($status->indexExists($indexName));
        $this->assertTrue($status->aliasExists($aliasName)->getWaitHandle()->join());

        // Deleting index should also remove alias
        $index->delete()->getWaitHandle()->join();

        $status->refresh()->getWaitHandle()->join();
        $this->assertFalse($status->indexExists($indexName));
        $this->assertFalse($status->aliasExists($aliasName)->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testAddAliasTwoIndices() : void
    {
        $indexName1 = 'test1';
        $indexName2 = 'test2';
        $aliasName = 'test-alias';

        $client = $this->_getClient();
        $index1 = $client->getIndex($indexName1);
        $index2 = $client->getIndex($indexName2);

        $index1->create(array(), true)->getWaitHandle()->join();
        $this->_waitForAllocation($index1);
        $index1->addAlias($aliasName)->getWaitHandle()->join();
        $index2->create(array(), true)->getWaitHandle()->join();
        $this->_waitForAllocation($index2);

        $index1->refresh()->getWaitHandle()->join();
        $index2->refresh()->getWaitHandle()->join();
        $index1->optimize()->getWaitHandle()->join();
        $index2->optimize()->getWaitHandle()->join();

        $status = Status::create($client)->getWaitHandle()->join();

        $this->assertTrue($status->indexExists($indexName1));
        $this->assertTrue($status->indexExists($indexName2));

        $this->assertTrue($status->aliasExists($aliasName)->getWaitHandle()->join());
        $this->assertTrue($index1->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());
        $this->assertFalse($index2->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());

        $index2->addAlias($aliasName)->getWaitHandle()->join();
        $this->assertTrue($index1->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());
        $this->assertTrue($index2->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testReplaceAlias() : void
    {
        $indexName1 = 'test1';
        $indexName2 = 'test2';
        $aliasName = 'test-alias';

        $client = $this->_getClient();
        $index1 = $client->getIndex($indexName1);
        $index2 = $client->getIndex($indexName2);

        $index1->create(array(), true)->getWaitHandle()->join();
        $index1->addAlias($aliasName)->getWaitHandle()->join();
        $index2->create(array(), true)->getWaitHandle()->join();

        $index1->refresh()->getWaitHandle()->join();
        $index2->refresh()->getWaitHandle()->join();

        $status = Status::create($client)->getWaitHandle()->join();

        $this->assertTrue($status->indexExists($indexName1));
        $this->assertTrue($status->indexExists($indexName2));
        $this->assertTrue($status->aliasExists($aliasName)->getWaitHandle()->join());
        $this->assertTrue($index1->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());
        $this->assertFalse($index2->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());

        $index2->addAlias($aliasName, true)->getWaitHandle()->join();
        $this->assertFalse($index1->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());
        $this->assertTrue($index2->getStatus()->getWaitHandle()->join()->hasAlias($aliasName)->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testAddDocumentVersion() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = new Type($index, 'test');

        $doc1 = new Document('1');
        $doc1->set('title', 'Hello world');

        $return = $type->addDocument($doc1)->getWaitHandle()->join();
        $data = $return->getData();
        $this->assertEquals(1, /* UNSAFE_EXPR */ $data['_version']);

        $return = $type->addDocument($doc1)->getWaitHandle()->join();
        $data = $return->getData();
        $this->assertEquals(2, /* UNSAFE_EXPR */ $data['_version']);
    }

    /**
     * @group functional
     */
    public function testClearCache() : void
    {
        $index = $this->_createIndex();
        $response = $index->clearCache()->getWaitHandle()->join();
        $this->assertFalse($response->hasError());
    }

    /**
     * @group functional
     */
    public function testFlush() : void
    {
        $index = $this->_createIndex();
        $response = $index->flush()->getWaitHandle()->join();
        $this->assertFalse($response->hasError());
    }

    /**
     * @group functional
     */
    public function testExists() : void
    {
        $index = $this->_createIndex();

        $this->assertTrue($index->exists()->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();

        $this->assertFalse($index->exists()->getWaitHandle()->join());
    }

    /**
     * Test $index->delete() return value for unknown index.
     *
     * Tests if deleting an index that does not exist in Elasticsearch,
     * correctly returns a boolean true from the hasError() method of
     * the \Elastica\Response object
     *
     * @group functional
     */
    public function testDeleteMissingIndexHasError() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('index_does_not_exist');

        try {
            $index->delete()->getWaitHandle()->join();
            $this->fail('This should never be reached. Deleting an unknown index will throw an exception');
        } catch (ResponseException $error) {
            $response = $error->getResponse();
            $this->assertTrue($response->hasError());
            $request = $error->getRequest();
            $this->assertInstanceOf('Elastica\Request', $request);
        }
    }

    /**
     * Tests to see if the test type mapping exists when calling $index->getMapping().
     *
     * @group functional
     */
    public function testIndexGetMapping() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $mapping = array('id' => array('type' => 'integer', 'store' => true), 'email' => array('type' => 'string', 'store' => 'no'),
            'username' => array('type' => 'string', 'store' => 'no'), 'test' => array('type' => 'integer', 'store' => 'no'),);

        $type->setMapping($mapping)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();
        $indexMappings = $index->getMapping()->getWaitHandle()->join();

        $this->assertEquals($indexMappings['test']['properties']['id']['type'], 'integer');
        $this->assertEquals($indexMappings['test']['properties']['id']['store'], true);
        $this->assertEquals($indexMappings['test']['properties']['email']['type'], 'string');
        $this->assertEquals($indexMappings['test']['properties']['username']['type'], 'string');
        $this->assertEquals($indexMappings['test']['properties']['test']['type'], 'integer');
    }

    /**
     * Tests to see if the index is empty when there are no types set.
     *
     * @group functional
     */
    public function testEmptyIndexGetMapping() : void
    {
        $index = $this->_createIndex();
        $indexMappings = $index->getMapping()->getWaitHandle()->join();

        $this->assertTrue(empty($indexMappings['elastica_test']));
    }

    /**
     * Test to see if search Default Limit works.
     *
     * @group functional
     */
    public function testLimitDefaultIndex() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('zero');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();

        $docs = array();

        $docs[] = new Document('1', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('2', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('3', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('4', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('5', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('6', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('7', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('8', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('9', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('10', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));
        $docs[] = new Document('11', array('id' => 1, 'email' => 'test@test.com', 'username' => 'farrelley'));

        $type = $index->getType('zeroType');
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        // default limit results  (default limit is 10)
        $resultSet = $index->search('farrelley')->getWaitHandle()->join();
        $this->assertEquals(10, $resultSet->count());

        // limit = 1
        $resultSet = $index->search('farrelley', 1)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @expectedException \Elastica\Exception\InvalidException
     *
     * @group functional
     */
    public function testCreateArray() : void
    {
        $client = $this->_getClient();
        $indexName = 'test';

        //Testing recreate (backward compatibility)
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $this->_waitForAllocation($index);
        $status = Status::create($client)->getWaitHandle()->join();
        $this->assertTrue($status->indexExists($indexName));

        //Testing create index with array options
        $opts = array('recreate' => true, 'routing' => 'r1,r2');
        $index->create(array(), $opts)->getWaitHandle()->join();
        $this->_waitForAllocation($index);
        $status = Status::create($client)->getWaitHandle()->join();
        $this->assertTrue($status->indexExists($indexName));

        //Testing invalid options
        $opts = array('recreate' => true, 'routing' => 'r1,r2', 'testing_invalid_option' => true);
        $index->create(array(), $opts)->getWaitHandle()->join();
        $this->_waitForAllocation($index);
        $status = Status::create($client)->getWaitHandle()->join();
        $this->assertTrue($status->indexExists($indexName));
    }

    /**
     * @group functional
     */
    public function testCreateSearch() : void
    {
        $client = $this->_getClient();
        $index = new Index($client, 'test');

        $query = new QueryString('test');
        $options = 5;

        $search = $index->createSearch($query, $options);

        $expected = Map {
            'query' => Map {
                'query_string' => Map {
                    'query' => 'test',
                },
            },
            'size' => 5,
        };
        $this->assertEquals($expected, $search->getQuery()->toArray());
        $this->assertEquals(array('test'), $search->getIndices());
        $this->assertTrue($search->hasIndices());
        $this->assertTrue($search->hasIndex('test'));
        $this->assertTrue($search->hasIndex($index));
        $this->assertEquals(array(), $search->getTypes());
        $this->assertFalse($search->hasTypes());
        $this->assertFalse($search->hasType('test_type'));

        $type = new Type($index, 'test_type2');
        $this->assertFalse($search->hasType($type));
    }

    /**
     * @group functional
     */
    public function testSearch() : void
    {
        $index = $this->_createIndex();

        $type = new Type($index, 'user');

        $docs = array();
        $docs[] = new Document('1', array('username' => 'hans', 'test' => array('2', '3', '5')));
        $docs[] = new Document('2', array('username' => 'john', 'test' => array('1', '3', '6')));
        $docs[] = new Document('3', array('username' => 'rolf', 'test' => array('2', '3', '7')));
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $index->search('rolf')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $count = $index->count('rolf')->getWaitHandle()->join();
        $this->assertEquals(1, $count);

        // Test if source is returned
        $result = $resultSet->current();
        $this->assertEquals(3, $result->getId());
        $data = $result->getData();
        $this->assertEquals('rolf', $data['username']);

        $count = $index->count()->getWaitHandle()->join();
        $this->assertEquals(3, $count);
    }

    /**
     * @group functional
     */
    public function testOptimize() : void
    {
        $index = $this->_createIndex();

        $type = new Type($index, 'optimize');

        $docs = array();
        $docs[] = new Document('1', array('foo' => 'bar'));
        $docs[] = new Document('2', array('foo' => 'bar'));
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $stats = $index->getStats()->getWaitHandle()->join()->getData();
        $this->assertEquals(0, /* UNSAFE_EXPR */ $stats['_all']['primaries']['docs']['deleted']);

        $type->deleteById(1)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $stats = $index->getStats()->getWaitHandle()->join()->getData();
        $this->assertEquals(1, /* UNSAFE_EXPR */ $stats['_all']['primaries']['docs']['deleted']);

        $index->optimize(array('max_num_segments' => 1))->getWaitHandle()->join();

        $stats = $index->getStats()->getWaitHandle()->join()->getData();
        $this->assertEquals(0, /* UNSAFE_EXPR */ $stats['_all']['primaries']['docs']['deleted']);
    }

    /**
     * @group functional
     */
    public function testAnalyze() : void
    {
        $index = $this->_createIndex();
        $index->optimize()->getWaitHandle()->join();
        sleep(2);
        $returnedTokens = $index->analyze('foo')->getWaitHandle()->join();

        $tokens = array(
            array(
                'token' => 'foo',
                'start_offset' => 0,
                'end_offset' => 3,
                'type' => '<ALPHANUM>',
                'position' => 1,
            ),
        );

        $this->assertEquals($tokens, $returnedTokens);
    }

    /**
     * Check for the presence of the mapper-attachments plugin and skip the current test if it is not found.
     */
    protected function _checkAttachmentsPlugin() : void
    {
        $nodes = $this->_getClient()->getCluster()->getWaitHandle()->join()->getNodes();
        if (!$nodes[0]->getInfo()->getWaitHandle()->join()->hasPlugin('mapper-attachments')->getWaitHandle()->join()) {
            $this->markTestSkipped('mapper-attachments plugin not installed');
        }
    }
}
