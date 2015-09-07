<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Exception\NotFoundException;
use Elastica\Exception\ResponseException;
use Elastica\Filter\Term;
use Elastica\Index;
use Elastica\Query;
use Elastica\Query\MatchAll;
use Elastica\Query\SimpleQueryString;
use Elastica\Script;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;
use Elastica\Type\Mapping;

class TypeTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testSearch() : void
    {
        $index = $this->_createIndex();

        $type = new Type($index, 'user');

        // Adds 1 document to the index
        $doc1 = new Document('1',
            array('username' => 'hans', 'test' => array('2', '3', '5'))
        );
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Adds a list of documents with _bulk upload to the index
        $docs = array();
        $docs[] = new Document('2',
            array('username' => 'john', 'test' => array('1', '3', '6'))
        );
        $docs[] = new Document('3',
            array('username' => 'rolf', 'test' => array('2', '3', '7'))
        );
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('rolf')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        $count = $type->count('rolf')->getWaitHandle()->join();
        $this->assertEquals(1, $count);

        // Test if source is returned
        $result = $resultSet->current();
        $this->assertEquals(3, $result->getId());
        $data = $result->getData();
        $this->assertEquals('rolf', $data['username']);
    }

    /**
     * @group functional
     */
    public function testCreateSearch() : void
    {
        $client = $this->_getClient();
        $index = new Index($client, 'test_index');
        $type = new Type($index, 'test_type');

        $query = new Query\QueryString('test');
        $options = array(
            'limit' => 5,
            'explain' => true,
        );

        $search = $type->createSearch($query, $options);

        $expected = Map {
            'query' => Map {
                'query_string' => Map {
                    'query' => 'test',
                },
            },
            'size' => 5,
            'explain' => true,
        };
        $this->assertEquals($expected, $search->getQuery()->toArray());
        $this->assertEquals(array('test_index'), $search->getIndices());
        $this->assertTrue($search->hasIndices());
        $this->assertTrue($search->hasIndex($index));
        $this->assertTrue($search->hasIndex('test_index'));
        $this->assertFalse($search->hasIndex('test'));
        $this->assertEquals(array('test_type'), $search->getTypes());
        $this->assertTrue($search->hasTypes());
        $this->assertTrue($search->hasType($type));
        $this->assertTrue($search->hasType('test_type'));
        $this->assertFalse($search->hasType('test_type2'));
    }

    /**
     * @group functional
     */
    public function testCreateSearchWithArray() : void
    {
        $client = $this->_getClient();
        $index = new Index($client, 'test_index');
        $type = new Type($index, 'test_type');

        $query = Map {
            'query' => Map {
                'query_string' => Map {
                    'query' => 'test',
                },
            },
        };

        $options = array(
            'limit' => 5,
            'explain' => true,
        );

        $search = $type->createSearch($query, $options);

        $expected = Map {
            'query' => Map {
                'query_string' => Map {
                    'query' => 'test',
                },
            },
            'size' => 5,
            'explain' => true,
        };
        $this->assertEquals($expected, $search->getQuery()->toArray());
        $this->assertEquals(array('test_index'), $search->getIndices());
        $this->assertTrue($search->hasIndices());
        $this->assertTrue($search->hasIndex($index));
        $this->assertTrue($search->hasIndex('test_index'));
        $this->assertFalse($search->hasIndex('test'));
        $this->assertEquals(array('test_type'), $search->getTypes());
        $this->assertTrue($search->hasTypes());
        $this->assertTrue($search->hasType($type));
        $this->assertTrue($search->hasType('test_type'));
        $this->assertFalse($search->hasType('test_type2'));
    }

    /**
     * @group functional
     */
    public function testNoSource() : void
    {
        $index = $this->_createIndex();

        $type = new Type($index, 'user');
        $mapping = new Mapping($type, array(
                'id' => array('type' => 'integer', 'store' => 'yes'),
                'username' => array('type' => 'string', 'store' => 'no'),
            ));
        $mapping->setSource(array('enabled' => false));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $mapping = $type->getMapping()->getWaitHandle()->join();

        $this->assertArrayHasKey('user', $mapping);
        $this->assertArrayHasKey('properties', $mapping['user']);
        $this->assertArrayHasKey('id', $mapping['user']['properties']);
        $this->assertArrayHasKey('type', $mapping['user']['properties']['id']);
        $this->assertEquals('integer', $mapping['user']['properties']['id']['type']);

        // Adds 1 document to the index
        $doc1 = new Document('1',
            array('username' => 'hans', 'test' => array('2', '3', '5'))
        );
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Adds a list of documents with _bulk upload to the index
        $docs = array();
        $docs[] = new Document('2',
            array('username' => 'john', 'test' => array('1', '3', '6'))
        );
        $docs[] = new Document('3',
            array('username' => 'rolf', 'test' => array('2', '3', '7'))
        );
        $type->addDocuments($docs)->getWaitHandle()->join();

        // To update index
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('rolf')->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());

        // Tests if no source is in response except id
        $result = $resultSet->current();
        $this->assertEquals(3, $result->getId());
        $this->assertEmpty($result->getData());
    }

    /**
     * @group functional
     */
    public function testDeleteById() : void
    {
        $index = $this->_createIndex();
        $type = new Type($index, 'user');

        // Adds hans, john and rolf to the index
        $docs = array(
            new Document('1', array('username' => 'hans', 'test' => array('2', '3', '5'))),
            new Document('2', array('username' => 'john', 'test' => array('1', '3', '6'))),
            new Document('3', array('username' => 'rolf', 'test' => array('2', '3', '7'))),
            new Document('foo/bar', array('username' => 'georg', 'test' => array('4', '2', '5'))),
        );
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        // sanity check for rolf
        $resultSet = $type->search('rolf')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
        $data = $resultSet->current()->getData();
        $this->assertEquals('rolf', $data['username']);

        // delete rolf
        $type->deleteById(3)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        // rolf should no longer be there
        $resultSet = $type->search('rolf')->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());

        // sanity check for id with slash
        $resultSet = $type->search('georg')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        // delete georg
        $type->deleteById('foo/bar')->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        // georg should no longer be there
        $resultSet = $type->search('georg')->getWaitHandle()->join();
        $this->assertEquals(0, $resultSet->count());

        // it should not be possible to delete the entire type with this method
        try {
            $type->deleteById('')->getWaitHandle()->join();
            $this->fail('Delete with empty string id should fail');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            $type->deleteById(' ')->getWaitHandle()->join();
            $this->fail('Delete with one space string id should fail');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            $type->deleteById(null)->getWaitHandle()->join();
            $this->fail('Delete with null id should fail');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            $type->deleteById(array())->getWaitHandle()->join();
            $this->fail('Delete with empty array id should fail');
        } catch (\InvalidArgumentException $e) {
            $this->assertTrue(true);
        }

        try {
            $type->deleteById('*')->getWaitHandle()->join();
            $this->fail('Delete request should fail because of invalid id: *');
        } catch (NotFoundException $e) {
            $this->assertTrue(true);
        }

        try {
            $type->deleteById('*:*')->getWaitHandle()->join();
            $this->fail('Delete request should fail because document with id *.* does not exist');
        } catch (NotFoundException $e) {
            $this->assertTrue(true);
        }

        try {
            $type->deleteById('!')->getWaitHandle()->join();
            $this->fail('Delete request should fail because document with id ! does not exist');
        } catch (NotFoundException $e) {
            $this->assertTrue(true);
        }

        $index->refresh()->getWaitHandle()->join();

        // rolf should no longer be there
        $resultSet = $type->search('john')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testDeleteDocument() : void
    {
        $index = $this->_createIndex();
        $type = new Type($index, 'user');

        // Adds hans, john and rolf to the index
        $docs = array(
            new Document('1', array('username' => 'hans', 'test' => array('2', '3', '5'))),
            new Document('2', array('username' => 'john', 'test' => array('1', '3', '6'))),
            new Document('3', array('username' => 'rolf', 'test' => array('2', '3', '7'))),
        );
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();
        $this->assertEquals(1, $document->getId());
        $this->assertEquals('hans', $document->get('username'));

        $this->assertEquals(3, $type->count()->getWaitHandle()->join());

        $type->deleteDocument($document)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        try {
            $type->getDocument('1')->getWaitHandle()->join();
            $this->fail('Document was not deleted');
        } catch (NotFoundException $e) {
            $this->assertTrue(true);
            $this->assertEquals(2, $type->count()->getWaitHandle()->join(), 'Documents count in type should be 2');
        }
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\NotFoundException
     */
    public function testGetDocumentNotExist() : void
    {
        $index = $this->_createIndex();
        $type = new Type($index, 'test');
        $type->addDocument(new Document('1', array('name' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $type->getDocument('1')->getWaitHandle()->join();

        $type->getDocument('2')->getWaitHandle()->join();
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\ResponseException
     */
    public function testGetDocumentNotExistingIndex() : void
    {
        $client = $this->_getClient();
        $index = new Index($client, 'index');
        $type = new Type($index, 'type');

        $type->getDocument('1')->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testDeleteByQueryWithQueryString() : void
    {
        $index = $this->_createIndex();
        $type = new Type($index, 'test');
        $type->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(1, $response->count());

        // Delete first document
        $response = $type->deleteByQuery('nicolas')->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        // Makes sure, document is deleted
        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(1, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(0, $response->count());
    }

    /**
     * @group functional
     */
    public function testDeleteByQueryWithQuery() : void
    {
        $index = $this->_createIndex();
        $type = new Type($index, 'test');
        $type->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(1, $response->count());

        // Delete first document
        $response = $type->deleteByQuery(new SimpleQueryString('nicolas'))->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        // Makes sure, document is deleted
        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(1, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(0, $response->count());
    }

    /**
     * @group functional
     */
    public function testDeleteByQueryWithQueryAndOptions() : void
    {
        $index = $this->_createIndex(null, true, 2);
        $type = new Type($index, 'test');
        $type->addDocument(new Document('1', array('name' => 'ruflin nicolas')))->getWaitHandle()->join();
        $type->addDocument(new Document('2', array('name' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(1, $response->count());

        // Route to the wrong document id; should not delete
        $response = $type->deleteByQuery(new SimpleQueryString('nicolas'), array('routing' => '2'))->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(2, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(1, $response->count());

        // Delete first document
        $response = $type->deleteByQuery(new SimpleQueryString('nicolas'), array('routing' => '1'))->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $index->refresh()->getWaitHandle()->join();

        // Makes sure, document is deleted
        $response = $index->search('ruflin*')->getWaitHandle()->join();
        $this->assertEquals(1, $response->count());

        $response = $index->search('nicolas')->getWaitHandle()->join();
        $this->assertEquals(0, $response->count());
    }

    /**
     * Test to see if Elastica_Type::getDocument() is properly using
     * the fields array when available instead of _source.
     *
     * @group functional
     */
    public function testGetDocumentWithFieldsSelection() : void
    {
        $index = $this->_createIndex();
        $type = new Type($index, 'test');
        $type->addDocument(new Document('1', array('name' => 'loris', 'country' => 'FR', 'email' => 'test@test.com')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $document = $type->getDocument('1', array('fields' => 'name,email'))->getWaitHandle()->join();
        $data = $document->getData();

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayNotHasKey('country', $data);
    }

    /**
     * Test to see if search Default Limit works.
     *
     * @group functional
     */
    public function testLimitDefaultType() : void
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

        // default results  (limit default is 10)
        $resultSet = $type->search('farrelley')->getWaitHandle()->join();
        $this->assertEquals(10, $resultSet->count());

        // limit = 1
        $resultSet = $type->search('farrelley', 1)->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());
    }

    /**
     * Test Delete of index type.  After delete will check for type mapping.
     *
     * @group functional
     */
    public function testDeleteType() : void
    {
        $index = $this->_createIndex();
        $type = new Type($index, 'test');
        $type->addDocuments(array(
            new Document('1', array('name' => 'ruflin nicolas')),
            new Document('2', array('name' => 'ruflin')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        // sleep a moment to be sure that all nodes in cluster has new type
        sleep(5);

        $type->delete()->getWaitHandle()->join();
        $index->optimize()->getWaitHandle()->join();

        $this->assertFalse($type->exists()->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testMoreLikeThisApi() : void
    {
        $client = $this->_getClient(array('persistent' => false));
        $index = $client->getIndex('elastica_test');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();

        $type = new Type($index, 'mlt_test');
        $type->addDocuments(array(
            new Document('1', array('visible' => true, 'name' => 'bruce wayne batman')),
            new Document('2', array('visible' => true, 'name' => 'bruce wayne')),
            new Document('3', array('visible' => false, 'name' => 'bruce wayne')),
            new Document('4', array('visible' => true, 'name' => 'batman')),
            new Document('5', array('visible' => false, 'name' => 'batman')),
            new Document('6', array('visible' => true, 'name' => 'superman')),
            new Document('7', array('visible' => true, 'name' => 'spiderman')),
        ))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();

        // Return all similar
        $resultSet = $type->moreLikeThis($document, array('min_term_freq' => '1', 'min_doc_freq' => '1'))->getWaitHandle()->join();
        $this->assertEquals(4, $resultSet->count());

        // Return just the visible similar
        $query = new Query();
        $filterTerm = new Term();
        $filterTerm->setTerm('visible', true);
        $query->setPostFilter($filterTerm);

        $resultSet = $type->moreLikeThis($document, array('min_term_freq' => '1', 'min_doc_freq' => '1'), $query)->getWaitHandle()->join();
        $this->assertEquals(2, $resultSet->count());
    }

    /**
     * @group functional
     */
    public function testUpdateDocument() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('elastica_test');
        $type = $index->getType('update_type');
        $id = '1';
        $type->addDocument(new Document($id, array('name' => 'bruce wayne batman', 'counter' => 1)))->getWaitHandle()->join();
        $newName = 'batman';

        $document = new Document();
        $script = new Script(
            'ctx._source.name = name; ctx._source.counter += count',
            Map {
                'name' => $newName,
                'count' => 2,
            },
            null,
            $id
        );
        $script->setUpsert($document);

        $type->updateDocument($script, array('refresh' => true))->getWaitHandle()->join();
        $updatedDoc = $type->getDocument($id)->getWaitHandle()->join()->getData();
        $this->assertEquals($newName, /* UNSAFE_EXPR */ $updatedDoc['name'], 'Name was not updated');
        $this->assertEquals(3, /* UNSAFE_EXPR */ $updatedDoc['counter'], 'Counter was not incremented');
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentWithIdForwardSlashes() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('elastica_test');
        $type = $index->getType('update_type');
        $id = '/id/with/forward/slashes';
        $type->addDocument(new Document($id, array('name' => 'bruce wayne batman', 'counter' => 1)))->getWaitHandle()->join();
        $newName = 'batman';

        $document = new Document();
        $script = new Script(
            'ctx._source.name = name; ctx._source.counter += count',
            Map {
                'name' => $newName,
                'count' => 2,
            },
            null,
            $id
        );
        $script->setUpsert($document);

        $type->updateDocument($script, array('refresh' => true))->getWaitHandle()->join();
        $updatedDoc = $type->getDocument($id)->getWaitHandle()->join()->getData();
        $this->assertEquals($newName, /* UNSAFE_EXPR */ $updatedDoc['name'], 'Name was not updated');
        $this->assertEquals(3, /* UNSAFE_EXPR */ $updatedDoc['counter'], 'Counter was not incremented');
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentWithParameter() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('elastica_test');
        $type = $index->getType('update_type');
        $id = '1';
        $type->addDocument(new Document($id, array('name' => 'bruce wayne batman', 'counter' => 1)))->getWaitHandle()->join();
        $newName = 'batman';

        $document = new Document();
        $script = new Script(
            'ctx._source.name = name; ctx._source.counter += count',
            Map {
                'name' => $newName,
                'count' => 2,
            },
            null,
            $id
        );
        $script->setUpsert($document);

        try {
            $type->updateDocument($script, array('version' => 999))->getWaitHandle()->join(); // Wrong version number to make the update fail
        } catch (ResponseException $e) {
            $this->assertContains('VersionConflictEngineException', $e->getMessage());
        }
        $updatedDoc = $type->getDocument($id)->getWaitHandle()->join()->getData();
        $this->assertNotEquals($newName, /* UNSAFE_EXPR */ $updatedDoc['name'], 'Name was updated');
        $this->assertNotEquals(3, /* UNSAFE_EXPR */ $updatedDoc['counter'], 'Counter was incremented');
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentWithFieldsSource() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('elastica_test');
        $type = $index->getType('update_type');

        $client->setConfigValue('document', array('autoPopulate' => true));

        $newDocument = new Document(null, array('counter' => 5, 'name' => 'Batman'));

        $this->assertFalse($newDocument->hasVersion());

        $response = $type->addDocument($newDocument)->getWaitHandle()->join();
        $responseData = $response->getData();

        $this->assertTrue($newDocument->hasVersion());
        $this->assertArrayHasKey('_version', $responseData, '_version is missing in response data it is weird');
        $this->assertEquals(1, /* UNSAFE_EXPR */ $responseData['_version']);
        $this->assertEquals(/* UNSAFE_EXPR */ $responseData['_version'], $newDocument->getVersion());

        $this->assertTrue($newDocument->hasId());

        $script = new Script('ctx._source.counter += count; ctx._source.realName = realName');
        $id = $newDocument->getId();
        if ( $id === null ) {
            throw new \RuntimeException( 'Null document id' );
        }
        $script->setId($id);
        $script->setParam('count', 7);
        $script->setParam('realName', 'Bruce Wayne');
        $script->setUpsert($newDocument);

        $newDocument->setFieldsSource();

        $response = $type->updateDocument($script)->getWaitHandle()->join();
        $responseData = $response->getData();

        $id = $newDocument->getId();
        if ( $id === null ) {
            throw new \RuntimeException( 'Null document id' );
        }
        $data = $type->getDocument($id)->getWaitHandle()->join()->getData();

        $this->assertEquals(12, /* UNSAFE_EXPR */ $data['counter']);
        $this->assertEquals('Batman', /* UNSAFE_EXPR */ $data['name']);
        $this->assertEquals('Bruce Wayne', /* UNSAFE_EXPR */ $data['realName']);

        $this->assertTrue($newDocument->hasVersion());
        $this->assertArrayHasKey('_version', $responseData, '_version is missing in response data it is weird');
        $this->assertEquals(2, /* UNSAFE_EXPR */ $responseData['_version']);

        $id = $newDocument->getId();
        if ( $id === null ) {
            throw new \RuntimeException( 'Null document id' );
        }
        $document = $type->getDocument($id);
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testUpdateDocumentWithoutId() : void
    {
        $index = $this->_createIndex();
        $this->_waitForAllocation($index);
        $type = $index->getType('elastica_type');

        $document = new Document();

        $type->updateDocument($document)->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentWithoutSource() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('elastica_type');

        $mapping = new Mapping();
        $mapping->setProperties(array(
            'name' => array(
                'type' => 'string',
                'store' => 'yes', ),
            'counter' => array(
                'type' => 'integer',
                'store' => 'no',
            ),
        ));
        $mapping->disableSource();
        $type->setMapping($mapping)->getWaitHandle()->join();

        $newDocument = new Document();
        $newDocument->setAutoPopulate();
        $newDocument->set('name', 'Batman');
        $newDocument->set('counter', 1);

        $type->addDocument($newDocument)->getWaitHandle()->join();

        $script = new Script('ctx._source.counter += count; ctx._source.name = name');
        $id = $newDocument->getId();
        if ($id === null) {
            throw new \RuntimeException('Document id is null');
        }
        $script->setId($id);
        $script->setParam('count', 2);
        $script->setParam('name', 'robin');

        $script->setUpsert($newDocument);

        try {
            $type->updateDocument($script)->getWaitHandle()->join();
            $this->fail('Update request should fail because source is disabled. Fields param is not set');
        } catch (ResponseException $e) {
            $this->assertContains('DocumentSourceMissingException', $e->getMessage());
        }

        $newDocument->setFieldsSource();

        try {
            $type->updateDocument($newDocument)->getWaitHandle()->join();
            $this->fail('Update request should fail because source is disabled. Fields param is set to _source');
        } catch (ResponseException $e) {
            $this->assertContains('DocumentSourceMissingException', $e->getMessage());
        }
    }

    /**
     * @group functional
     */
    public function testAddDocumentHashId() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test2');

        $hashId = '#1';

        $doc = new Document($hashId, array('name' => 'ruflin'));
        $type->addDocument($doc)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $search = new Search($index->getClient());
        $search->addIndex($index);
        $resultSet = $search->search(new MatchAll())->getWaitHandle()->join();
        $this->assertEquals($hashId, $resultSet->current()->getId());

        $doc = $type->getDocument($hashId)->getWaitHandle()->join();
        $this->assertEquals($hashId, $doc->getId());
    }

    /**
     * @group functional
     */
    public function testAddDocumentAutoGeneratedId() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('elastica_type');

        $document = new Document();
        $document->setAutoPopulate();
        $document->set('name', 'ruflin');
        $this->assertEquals('', $document->getId());
        $this->assertFalse($document->hasId());

        $type->addDocument($document)->getWaitHandle()->join();

        $this->assertNotEquals('', $document->getId());
        $this->assertTrue($document->hasId());

        $id = $document->getId();
        if ( $id === null ) {
            throw new \RuntimeException( 'Null document id' );
        }
        $foundDoc = $type->getDocument($id)->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\Document', $foundDoc);
        $this->assertEquals($document->getId(), $foundDoc->getId());
        $data = $foundDoc->getData();
        $this->assertArrayHasKey('name', $data);
        $this->assertEquals('ruflin', /* UNSAFE_EXPR */ $data['name']);
    }

    /**
     * @group functional
     * @expectedException \Elastica\Exception\RuntimeException
     */
    public function testAddDocumentWithoutSerializer() : void
    {
        $index = $this->_createIndex();
        $this->_waitForAllocation($index);

        $type = new Type($index, 'user');

        $type->addObject(new \stdClass())->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testAddObject() : void
    {
        $index = $this->_createIndex();

        $type = new Type($index, 'user');
        $type->setSerializer(function (mixed $input) : string {
            return json_encode($input);
        });

        $userObject = new \stdClass();
        $userObject->username = 'hans';
        $userObject->test = array('2', '3', '5');

        $type->addObject($userObject)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('hans')->getWaitHandle()->join();
        $this->assertEquals(1, $resultSet->count());

        // Test if source is returned
        $result = $resultSet->current();
        $data = $result->getData();
        $this->assertEquals('hans', $data['username']);
    }

    /**
     * @group unit
     */
    public function testSetSerializer() : void
    {
        $index = $this->_getClient()->getIndex('foo');
        $type = $index->getType('user');
        $ret = $type->setSerializer(function (mixed $input) : string {
            return get_object_vars($input);
        });
        $this->assertInstanceOf('Elastica\Type', $ret);
    }

    /**
     * @group functional
     */
    public function testExists() : void
    {
        $index = $this->_createIndex();
        $this->assertTrue($index->exists()->getWaitHandle()->join());

        $type = new Type($index, 'user');
        $this->assertFalse($type->exists()->getWaitHandle()->join());

        $type->addDocument(new Document('1', array('name' => 'test name')))->getWaitHandle()->join();
        $index->optimize()->getWaitHandle()->join();

        // sleep a moment to be sure that all nodes in cluster has new type
        sleep(5);

        //Test if type exists
        $this->assertTrue($type->exists()->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
        $this->assertFalse($index->exists()->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testGetMapping() : void
    {
        $typeName = 'test-type';

        $index = $this->_createIndex();
        $indexName = $index->getName();
        $type = new Type($index, $typeName);
        $mapping = new Mapping($type, $expect = array(
            'id' => array('type' => 'integer', 'store' => true),
        ));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $client = $index->getClient();

        $this->assertEquals(
            array('test-type' => array('properties' => $expect)),
            $client->getIndex($indexName)->getType($typeName)->getMapping()->getWaitHandle()->join()
        );
    }

    /**
     * @group functional
     */
    public function testGetMappingAlias() : void
    {
        $aliasName = 'test-alias';
        $typeName = 'test-alias-type';

        $index = $this->_createIndex();
        $index->addAlias($aliasName)->getWaitHandle()->join();
        $type = new Type($index, $typeName);
        $mapping = new Mapping($type, $expect = array(
            'id' => array('type' => 'integer', 'store' => true),
        ));
        $type->setMapping($mapping)->getWaitHandle()->join();

        $client = $index->getClient();

        $this->assertEquals(
            array('test-alias-type' => array('properties' => $expect)),
            $client->getIndex($aliasName)->getType($typeName)->getMapping()->getWaitHandle()->join()
        );
    }
}
