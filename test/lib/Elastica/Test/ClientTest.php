<?hh
namespace Elastica\Test;

use Elastica\Connection;
use Elastica\Document;
use Elastica\Exception\Connection\HttpException;
use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\Request;
use Elastica\Script;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;

class ClientTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $client = $this->_getClient();
        $this->assertCount(1, $client->getConnections());
    }

    /**
     * @group functional
     */
    public function testConnectionsArray() : void
    {
        // Creates a new index 'xodoa' and a type 'user' inside this index
        $client = $this->_getClient(array('connections' => array(array('host' => $this->_getHost(), 'port' => 9200))));
        $index = $client->getIndex('elastica_test1');
        $index->create(array(), true)->getWaitHandle()->join();

        $type = $index->getType('user');

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

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('rolf');
    }

    /**
     * @group functional
     */
    public function testTwoServersSame() : void
    {
        // Creates a new index 'xodoa' and a type 'user' inside this index
        $client = $this->_getClient(array('connections' => array(
            array('host' => $this->_getHost(), 'port' => 9200),
            array('host' => $this->_getHost(), 'port' => 9200),
        )));
        $index = $client->getIndex('elastica_test1');
        $index->create(array(), true)->getWaitHandle()->join();

        $type = $index->getType('user');

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

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $resultSet = $type->search('rolf');
    }

    /**
     * @group unit
     */
    public function testConnectionParamsArePreparedForConnectionsOption() : void
    {
        $url = 'https://'.$this->_getHost().':9200';
        $client = $this->_getClient(array('connections' => array(array('url' => $url))));
        $connection = $client->getConnection();

        $this->assertEquals($url, $connection->getConfig('url'));
    }

    /**
     * @group unit
     */
    public function testConnectionParamsArePreparedForServersOption() : void
    {
        $url = 'https://'.$this->_getHost().':9200';
        $client = $this->_getClient(array('servers' => array(array('url' => $url))));
        $connection = $client->getConnection();

        $this->assertEquals($url, $connection->getConfig('url'));
    }

    /**
     * @group unit
     */
    public function testConnectionParamsArePreparedForDefaultOptions() : void
    {
        $url = 'https://'.$this->_getHost().':9200';
        $client = $this->_getClient(array('url' => $url));
        $connection = $client->getConnection();

        $this->assertEquals($url, $connection->getConfig('url'));
    }

    /**
     * @group functional
     */
    public function testBulk() : void
    {
        $client = $this->_getClient();

        $params = array(
            array('index' => array('_index' => 'test', '_type' => 'user', '_id' => '1')),
            array('user' => array('name' => 'hans')),
            array('index' => array('_index' => 'test', '_type' => 'user', '_id' => '2')),
            array('user' => array('name' => 'peter')),
        );

        $client->bulk($params)->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testOptimizeAll() : void
    {
        $client = $this->_getClient();
        $response = $client->optimizeAll()->getWaitHandle()->join();

        $this->assertFalse($response->hasError());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testAddDocumentsEmpty() : void
    {
        $client = $this->_getClient();
        $client->addDocuments(array())->getWaitHandle()->join();
    }

    /**
     * Test bulk operations on Index.
     *
     * @group functional
     */
    public function testBulkIndex() : void
    {
        $index = $this->_getClient()->getIndex('cryptocurrencies');

        $anonCoin = new Document('1', array('name' => 'anoncoin'), 'altcoin');
        $ixCoin = new Document('2', array('name' => 'ixcoin'), 'altcoin');

        $index->addDocuments(array($anonCoin, $ixCoin))->getWaitHandle()->join();

        $this->assertEquals('anoncoin', $index->getType('altcoin')->getDocument('1')->getWaitHandle()->join()->get('name'));
        $this->assertEquals('ixcoin', $index->getType('altcoin')->getDocument('2')->getWaitHandle()->join()->get('name'));

        $index->updateDocuments(array(
            new Document('1', array('name' => 'AnonCoin'), 'altcoin'),
            new Document('2', array('name' => 'iXcoin'), 'altcoin'),
        ))->getWaitHandle()->join();

        $this->assertEquals('AnonCoin', $index->getType('altcoin')->getDocument('1')->getWaitHandle()->join()->get('name'));
        $this->assertEquals('iXcoin', $index->getType('altcoin')->getDocument('2')->getWaitHandle()->join()->get('name'));

        $ixCoin->setIndex(null);  // Make sure the index gets set properly if missing
        $index->deleteDocuments(array($anonCoin, $ixCoin))->getWaitHandle()->join();

        $this->setExpectedException('Elastica\Exception\NotFoundException');
        $index->getType('altcoin')->getDocument('1')->getWaitHandle()->join();
        $index->getType('altcoin')->getDocument('2')->getWaitHandle()->join();
    }

    /**
     * Test bulk operations on Type.
     *
     * @group functional
     */
    public function testBulkType() : void
    {
        $type = $this->_getClient()->getIndex('cryptocurrencies')->getType('altcoin');

        $liteCoin = new Document('1', array('name' => 'litecoin'));
        $nameCoin = new Document('2', array('name' => 'namecoin'));

        $type->addDocuments(array($liteCoin, $nameCoin))->getWaitHandle()->join();

        $this->assertEquals('litecoin', $type->getDocument('1')->getWaitHandle()->join()->get('name'));
        $this->assertEquals('namecoin', $type->getDocument('2')->getWaitHandle()->join()->get('name'));

        $type->updateDocuments(array(
            new Document('1', array('name' => 'LiteCoin')),
            new Document('2', array('name' => 'NameCoin')),
        ))->getWaitHandle()->join();

        $this->assertEquals('LiteCoin', $type->getDocument('1')->getWaitHandle()->join()->get('name'));
        $this->assertEquals('NameCoin', $type->getDocument('2')->getWaitHandle()->join()->get('name'));

        $nameCoin->setType(null);  // Make sure the type gets set properly if missing
        $type->deleteDocuments(array($liteCoin, $nameCoin))->getWaitHandle()->join();

        $this->setExpectedException('Elastica\Exception\NotFoundException');
        $type->getDocument('1')->getWaitHandle()->join();
        $type->getDocument('2')->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testUpdateDocuments() : void
    {
        $indexName = 'test';
        $typeName = 'people';

        $client = $this->_getClient();
        $type = $client->getIndex($indexName)->getType($typeName);

        $initialValue = 28;
        $modifiedValue = 27;

        $doc1 = new Document(
            '1',
            array('name' => 'hans', 'age' => $initialValue),
            $typeName,
            $indexName
        );
        $doc2 = new Document(
            '2',
            array('name' => 'anna', 'age' => $initialValue),
            $typeName,
            $indexName
        );
        $data = array($doc1, $doc2);
        $client->addDocuments($data)->getWaitHandle()->join();

        foreach ($data as $i => $doc) {
            $data[$i]->set('age', $modifiedValue);
        }
        $client->updateDocuments($data)->getWaitHandle()->join();

        $docData1 = $type->getDocument('1')->getWaitHandle()->join()->getData();
        $docData2 = $type->getDocument('2')->getWaitHandle()->join()->getData();

        $this->assertEquals($modifiedValue, /* UNSAFE_EXPR */ $docData1['age']);
        $this->assertEquals($modifiedValue, /* UNSAFE_EXPR */ $docData2['age']);
    }

    /**
     * Test deleteIds method using string parameters.
     *
     * This test ensures that the deleteIds method of
     * the \Elastica\Client can properly accept and use
     * an $index parameter and $type parameter that are
     * strings
     *
     * This test is a bit more verbose than just sending the
     * values to deleteIds and checking for exceptions or
     * warnings.
     *
     * It will add a document, search for it, then delete it
     * using the parameter types we are interested in, and then
     * re-search to verify that they have been deleted
     *
     * @group functional
     */
    public function testDeleteIdsIdxStringTypeString() : void
    {
        $data = array('username' => 'hans');
        $userSearch = 'username:hans';

        $index = $this->_createIndex(null, true, 2);

        // Create the index, deleting it first if it already exists
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('user');

        // Adds 1 document to the index
        $doc = new Document(null, $data);
        $doc->setRouting('1');
        $result = $type->addDocument($doc)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $resultData = $result->getData();
        $ids = array(/* UNSAFE_EXPR */ $resultData['_id']);

        // Check to make sure the document is in the index
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(1, $totalHits);

        // And verify that the variables we are doing to send to
        // deleteIds are the type we are testing for
        $idxString = $index->getName();
        $typeString = $type->getName();
        $this->assertTrue(is_string($idxString));
        $this->assertTrue(is_string($typeString));

        // Try to delete doc with a routing value which hashes to
        // a different shard then the id.
        $resp = $index->getClient()->deleteIds($ids, $index, $type, 2);

        // Refresh the index
        $index->refresh()->getWaitHandle()->join();

        // Research the index to verify that the items are still there
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(1, $totalHits);

        // Using the existing $index and $type variables which
        // are \Elastica\Index and \Elastica\Type objects respectively
        $resp = $index->getClient()->deleteIds($ids, $index, $type, 1)->getWaitHandle()->join();

        // Refresh the index to clear out deleted ID information
        $index->refresh()->getWaitHandle()->join();

        // Research the index to verify that the items have been deleted
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(0, $totalHits);
    }

    /**
     * Test deleteIds method using string parameter for $index
     * and object parameter for $type.
     *
     * This test ensures that the deleteIds method of
     * the \Elastica\Client can properly accept and use
     * an $index parameter that is a string and a $type
     * parameter that is of type \Elastica\Type
     *
     * This test is a bit more verbose than just sending the
     * values to deleteIds and checking for exceptions or
     * warnings.
     *
     * It will add a document, search for it, then delete it
     * using the parameter types we are interested in, and then
     * re-search to verify that they have been deleted
     *
     * @group functional
     */
    public function testDeleteIdsIdxStringTypeObject() : void
    {
        $data = array('username' => 'hans');
        $userSearch = 'username:hans';

        $index = $this->_createIndex();

        // Create the index, deleting it first if it already exists
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('user');

        // Adds 1 document to the index
        $doc = new Document(null, $data);
        $result = $type->addDocument($doc)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $resultData = $result->getData();
        $ids = array(/* UNSAFE_EXPR */ $resultData['_id']);

        // Check to make sure the document is in the index
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(1, $totalHits);

        // And verify that the variables we are doing to send to
        // deleteIds are the type we are testing for
        $idxString = $index->getName();
        $this->assertTrue(is_string($idxString));
        $this->assertInstanceOf('Elastica\Type', $type);

        // Using the existing $index and $type variables which
        // are \Elastica\Index and \Elastica\Type objects respectively
        $resp = $index->getClient()->deleteIds($ids, $index, $type)->getWaitHandle()->join();

        // Refresh the index to clear out deleted ID information
        $index->refresh()->getWaitHandle()->join();

        // Research the index to verify that the items have been deleted
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(0, $totalHits);
    }

    /**
     * Test deleteIds method using object parameter for $index
     * and string parameter for $type.
     *
     * This test ensures that the deleteIds method of
     * the \Elastica\Client can properly accept and use
     * an $index parameter that is  of type Elasitca_Index
     * and a $type parameter that is a string
     *
     * This test is a bit more verbose than just sending the
     * values to deleteIds and checking for exceptions or
     * warnings.
     *
     * It will add a document, search for it, then delete it
     * using the parameter types we are interested in, and then
     * re-search to verify that they have been deleted
     *
     * @group functional
     */
    public function testDeleteIdsIdxObjectTypeString() : void
    {
        $data = array('username' => 'hans');
        $userSearch = 'username:hans';

        $index = $this->_createIndex();

        // Create the index, deleting it first if it already exists
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('user');

        // Adds 1 document to the index
        $doc = new Document(null, $data);
        $result = $type->addDocument($doc)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $resultData = $result->getData();
        $ids = array(/* UNSAFE_EXPR */ $resultData['_id']);

        // Check to make sure the document is in the index
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(1, $totalHits);

        // And verify that the variables we are doing to send to
        // deleteIds are the type we are testing for
        $typeString = $type->getName();
        $this->assertInstanceOf('Elastica\Index', $index);
        $this->assertTrue(is_string($typeString));

        // Using the existing $index and $type variables which
        // are \Elastica\Index and \Elastica\Type objects respectively
        $resp = $index->getClient()->deleteIds($ids, $index, $type)->getWaitHandle()->join();

        // Refresh the index to clear out deleted ID information
        $index->refresh()->getWaitHandle()->join();

        // Research the index to verify that the items have been deleted
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(0, $totalHits);
    }

    /**
     * Test deleteIds method using object parameter for $index
     * and object parameter for $type.
     *
     * This test ensures that the deleteIds method of
     * the \Elastica\Client can properly accept and use
     * an $index parameter that is an object and a $type
     * parameter that is of type \Elastica\Type
     *
     * This test is a bit more verbose than just sending the
     * values to deleteIds and checking for exceptions or
     * warnings.
     *
     * It will add a document, search for it, then delete it
     * using the parameter types we are interested in, and then
     * re-search to verify that they have been deleted
     *
     * @group functional
     */
    public function testDeleteIdsIdxObjectTypeObject() : void
    {
        $data = array('username' => 'hans');
        $userSearch = 'username:hans';

        $index = $this->_createIndex();

        // Create the index, deleting it first if it already exists
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('user');

        // Adds 1 document to the index
        $doc = new Document(null, $data);
        $result = $type->addDocument($doc)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $resultData = $result->getData();
        $ids = array(/* UNSAFE_EXPR */ $resultData['_id']);

        // Check to make sure the document is in the index
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(1, $totalHits);

        // And verify that the variables we are doing to send to
        // deleteIds are the type we are testing for
        $this->assertInstanceOf('Elastica\Index', $index);
        $this->assertInstanceOf('Elastica\Type', $type);

        // Using the existing $index and $type variables which
        // are \Elastica\Index and \Elastica\Type objects respectively
        $resp = $index->getClient()->deleteIds($ids, $index, $type)->getWaitHandle()->join();

        // Refresh the index to clear out deleted ID information
        $index->refresh()->getWaitHandle()->join();

        // Research the index to verify that the items have been deleted
        $resultSet = $type->search($userSearch)->getWaitHandle()->join();
        $totalHits = $resultSet->getTotalHits();
        $this->assertEquals(0, $totalHits);
    }

    /**
     * @group functional
     */
    public function testOneInvalidConnection() : void
    {
        $client = $this->_getClient();

        // First connection work, second should not work
        $connection1 = new Connection(Map {'port' => '9100', 'timeout' => 2, 'host' => $this->_getHost()});
        $connection2 = new Connection(Map {'port' => '9200', 'timeout' => 2, 'host' => $this->_getHost()});

        $client->setConnections(array($connection1, $connection2));

        $client->request('_status', Request::GET)->getWaitHandle()->join();

        $connections = $client->getConnections();

        // two connections are setup
        $this->assertEquals(2, count($connections));

        // One connection has to be disabled
        $this->assertTrue($connections[0]->isEnabled() == false || $connections[1]->isEnabled() == false);
    }

    /**
     * @group functional
     */
    public function testTwoInvalidConnection() : void
    {
        $client = $this->_getClient();

        // First connection work, second should not work
        $connection1 = new Connection(Map {'port' => '9101', 'timeout' => 2});
        $connection2 = new Connection(Map {'port' => '9102', 'timeout' => 2});

        $client->setConnections(array($connection1, $connection2));

        try {
            $client->request('_status', Request::GET)->getWaitHandle()->join();
            $this->fail('Should throw exception as no connection valid');
        } catch (HttpException $e) {
        }

        $connections = $client->getConnections();

        // two connections are setup
        $this->assertEquals(2, count($connections));

        // One connection has to be disabled
        $this->assertTrue($connections[0]->isEnabled() == false || $connections[1]->isEnabled() == false);
    }

    /**
     * Tests if the callback works in case a connection is down.
     *
     * @group functional
     */
    public function testCallback() : void
    {
        $count = 0;
        $object = $this;

        // Callback function which verifies that disabled connection objects are returned
        $callback = function ($connection, $exception, $client) use (&$object, &$count) {
            $object->assertInstanceOf('Elastica\Connection', $connection);
            $object->assertInstanceOf('Elastica\Exception\ConnectionException', $exception);
            $object->assertInstanceOf('Elastica\Client', $client);
            $object->assertFalse($connection->isEnabled());
            ++$count;
        };

        $client = $this->_getClient(array(), $callback);

        // First connection work, second should not work
        $connection1 = new Connection(Map {'port' => '9101', 'timeout' => 2});
        $connection2 = new Connection(Map {'port' => '9102', 'timeout' => 2});

        $client->setConnections(array($connection1, $connection2));

        $this->assertEquals(0, $count);

        try {
            $client->request('_status', Request::GET)->getWaitHandle()->join();
            $this->fail('Should throw exception as no connection valid');
        } catch (HttpException $e) {
            $this->assertTrue(true);
        }

        // Two disabled connections (from closure call)
        $this->assertEquals(2, $count);
    }

    /**
     * @group functional
     */
    public function testUrlConstructor() : void
    {
        $url = 'http://'.$this->_getHost().':9200/';

        // Url should overwrite invalid host
        $client = $this->_getClient(array('url' => $url, 'port' => '9101', 'timeout' => 2));

        $response = $client->request('_status')->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\Response', $response);
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentByDocument() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        $newDocument = new Document('1', array('field1' => 'value1', 'field2' => 'value2'));
        $type->addDocument($newDocument)->getWaitHandle()->join();

        $updateDocument = new Document('1', array('field2' => 'value2changed', 'field3' => 'value3added'));
        $client->updateDocument('1', $updateDocument, $index->getName(), $type->getName())->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('value2changed', /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayHasKey('field3', $data);
        $this->assertEquals('value3added', /* UNSAFE_EXPR */ $data['field3']);
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentByScript() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        $newDocument = new Document('1', array('field1' => 'value1', 'field2' => 10, 'field3' => 'should be removed', 'field4' => 'should be changed'));
        $type->addDocument($newDocument)->getWaitHandle()->join();

        $script = new Script('ctx._source.field2 += 5; ctx._source.remove("field3"); ctx._source.field4 = "changed"');
        $client->updateDocument('1', $script, $index->getName(), $type->getName())->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals(15, /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('changed', /* UNSAFE_EXPR */ $data['field4']);
        $this->assertArrayNotHasKey('field3', $data);
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentByScriptWithUpsert() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        $script = new Script('ctx._source.field2 += count; ctx._source.remove("field3"); ctx._source.field4 = "changed"');
        $script->setParam('count', 5);
        $script->setUpsert(array('field1' => 'value1', 'field2' => 10, 'field3' => 'should be removed', 'field4' => 'value4'));

        // should use document fields because document does not exist, script is avoided
        $client->updateDocument('1', $script, $index->getName(), $type->getName(), array('fields' => '_source'))->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals(10, /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayHasKey('field3', $data);
        $this->assertEquals('should be removed', /* UNSAFE_EXPR */ $data['field3']);
        $this->assertArrayHasKey('field4', $data);
        $this->assertEquals('value4', /* UNSAFE_EXPR */ $data['field4']);

        // should use script because document exists, document values are ignored
        $client->updateDocument('1', $script, $index->getName(), $type->getName(), array('fields' => '_source'))->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals(15, /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayHasKey('field4', $data);
        $this->assertEquals('changed', /* UNSAFE_EXPR */ $data['field4']);
        $this->assertArrayNotHasKey('field3', $data);
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentByRawData() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        $newDocument = new Document('1', array('field1' => 'value1'));
        $type->addDocument($newDocument)->getWaitHandle()->join();

        $rawData = array(
            'doc' => array(
                'field2' => 'value2',
            ),
        );

        $response = $client->updateDocument('1', $rawData, $index->getName(), $type->getName(), array('retry_on_conflict' => 1))->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $document = $type->getDocument('1')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('value2', /* UNSAFE_EXPR */ $data['field2']);
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentByDocumentWithUpsert() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        $newDocument = new Document('1', array('field1' => 'value1updated', 'field2' => 'value2updated'));
        $upsert = new Document('1', array('field1' => 'value1', 'field2' => 'value2'));
        $newDocument->setUpsert($upsert);
        $client->updateDocument('1', $newDocument, $index->getName(), $type->getName(), array('fields' => '_source'))->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('value2', /* UNSAFE_EXPR */ $data['field2']);

        // should use update document because document exists, upsert document values are ignored
        $client->updateDocument('1', $newDocument, $index->getName(), $type->getName(), array('fields' => '_source'))->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1updated', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('value2updated', /* UNSAFE_EXPR */ $data['field2']);
    }

    /**
     * @group functional
     */
    public function testDocAsUpsert() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        //Confirm document one does not exist
        try {
            $document = $type->getDocument('1')->getWaitHandle()->join();
            $this->fail('Exception was not thrown. Maybe the document exists?');
        } catch (\Exception $e) {
            //Ignore the exception because we expect the document to not exist.
        }

        $newDocument = new Document('1', array('field1' => 'value1', 'field2' => 'value2'));
        $newDocument->setDocAsUpsert(true);
        $client->updateDocument('1', $newDocument, $index->getName(), $type->getName(), array('fields' => '_source'))->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\Document', $document);
        $data = $document->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals('value2', /* UNSAFE_EXPR */ $data['field2']);
    }

    /**
     * @group functional
     */
    public function testUpdateWithInvalidType() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        //Try to update using a stdClass object
        $badDocument = new \stdClass();

        try {
            $client->updateDocument('1', $badDocument, $index->getName(), $type->getName())->getWaitHandle()->join();
            $this->fail('Tried to update using an object that is not a Document or a Script but no exception was thrown');
        } catch (\Exception $e) {
            //Good. An exception was thrown.
        }
    }

    /**
     * @group functional
     */
    public function testDeleteDocuments() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        $docs = array(
            new Document('1', array('field' => 'value1'), $type, $index),
            new Document('2', array('field' => 'value2'), $type, $index),
            new Document('3', array('field' => 'value3'), $type, $index),
        );

        $response = $client->addDocuments($docs)->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Bulk\ResponseSet', $response);
        $this->assertEquals(3, count($response));
        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());
        $this->assertEquals('', $response->getError());

        $index->refresh()->getWaitHandle()->join();

        $this->assertEquals(3, $type->count()->getWaitHandle()->join());

        $deleteDocs = array(
            $docs[0],
            $docs[2],
        );

        $response = $client->deleteDocuments($deleteDocs)->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Bulk\ResponseSet', $response);
        $this->assertEquals(2, count($response));
        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());
        $this->assertEquals('', $response->getError());

        $index->refresh()->getWaitHandle()->join();

        $this->assertEquals(1, $type->count()->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testLastRequestResponse() : void
    {
        $client = $this->_getClient();
        $response = $client->request('_status')->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Response', $response);

        
        $lastRequest = $client->getLastRequest();
        if ($lastRequest === null) {
            $this->fail('$lastRequest === null');
        } else {
            $this->assertInstanceOf('Elastica\Request', $lastRequest);
            $this->assertEquals('_status', $lastRequest->getPath());
        }
    
        $lastResponse = $client->getLastResponse();
        $this->assertInstanceOf('Elastica\Response', $lastResponse);
        $this->assertSame($response, $lastResponse);
    }

    /**
     * @group functional
     */
    public function testUpdateDocumentPopulateFields() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');
        $client = $index->getClient();

        $newDocument = new Document('1', array('field1' => 'value1', 'field2' => 10, 'field3' => 'should be removed', 'field4' => 'value4'));
        $newDocument->setAutoPopulate();
        $type->addDocument($newDocument)->getWaitHandle()->join();

        $script = new Script('ctx._source.field2 += count; ctx._source.remove("field3"); ctx._source.field4 = "changed"');
        $script->setParam('count', 5);
        $script->setUpsert($newDocument);

        $client->updateDocument(
            '1',
            $script,
            $index->getName(),
            $type->getName(),
            array('fields' => '_source')
        )->getWaitHandle()->join();

        $data = $type->getDocument('1')->getWaitHandle()->join()->getData();
        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('value1', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals(15, /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayHasKey('field4', $data);
        $this->assertEquals('changed', /* UNSAFE_EXPR */ $data['field4']);
        $this->assertArrayNotHasKey('field3', $data);

        $script = new Script('ctx._source.field2 += count; ctx._source.remove("field4"); ctx._source.field1 = field1;');
        $script->setParam('count', 5);
        $script->setParam('field1', 'updated');
        $script->setUpsert($newDocument);

        $client->updateDocument(
            '1',
            $script,
            $index->getName(),
            $type->getName(),
            array('fields' => 'field2,field4')
        )->getWaitHandle()->join();

        $document = $type->getDocument('1')->getWaitHandle()->join();

        $data = $document->getData();

        $this->assertArrayHasKey('field1', $data);
        $this->assertEquals('updated', /* UNSAFE_EXPR */ $data['field1']);
        $this->assertArrayHasKey('field2', $data);
        $this->assertEquals(20, /* UNSAFE_EXPR */ $data['field2']);
        $this->assertArrayNotHasKey('field3', $data);
        $this->assertArrayNotHasKey('field4', $data);
    }

    /**
     * @group functional
     */
    public function testAddDocumentsWithoutIds() : void
    {
        $docs = array();
        for ($i = 0; $i < 10; ++$i) {
            $docs[] = new Document(null, array('pos' => $i));
        }

        foreach ($docs as $doc) {
            $this->assertFalse($doc->hasId());
        }

        $index = $this->_createIndex();

        $client = $index->getClient();
        $client->setConfigValue('document', array('autoPopulate' => true));

        $type = $index->getType('pos');
        $type->addDocuments($docs)->getWaitHandle()->join();

        foreach ($docs as $doc) {
            $this->assertTrue($doc->hasId());
            $this->assertTrue($doc->hasVersion());
            $this->assertEquals(1, $doc->getVersion());
        }
    }

    /**
     * @group unit
     */
    public function testConfigValue() : void
    {
        $config = array(
            'level1' => array(
                'level2' => array(
                    'level3' => 'value3',
                ),
                'level21' => 'value21',
            ),
            'level11' => 'value11',
        );
        $client = $this->_getClient($config);

        $this->assertNull($client->getConfigValue('level12'));
        $this->assertFalse($client->getConfigValue('level12', false));
        $this->assertEquals(10, $client->getConfigValue('level12', 10));

        $this->assertEquals('value11', $client->getConfigValue('level11'));
        $this->assertNotNull($client->getConfigValue('level11'));
        $this->assertNotEquals(false, $client->getConfigValue('level11', false));
        $this->assertNotEquals(10, $client->getConfigValue('level11', 10));

        $this->assertEquals('value3', $client->getConfigValue(array('level1', 'level2', 'level3')));
        $this->assertInternalType('array', $client->getConfigValue(array('level1', 'level2')));
    }

    /**
     * @group functional
     */
    public function testArrayQuery() : void
    {
        $client = $this->_getClient();

        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');
        $type->addDocument(new Document('1', array('username' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $query = array(
            'query' => array(
                'query_string' => array(
                    'query' => 'ruflin',
                ),
            ),
        );

        $path = $index->getName().'/'.$type->getName().'/_search';

        $response = $client->request($path, Request::GET, $query)->getWaitHandle()->join();
        $responseArray = $response->getData();

        $this->assertEquals(1, /* UNSAFE_EXPR */ $responseArray['hits']['total']);
    }

    /**
     * @group functional
     */
    public function testJSONQuery() : void
    {
        $client = $this->_getClient();

        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('test');
        $type->addDocument(new Document('1', array('username' => 'ruflin')))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $query = '{"query":{"query_string":{"query":"ruflin"}}}';

        $path = $index->getName().'/'.$type->getName().'/_search';

        $response = $client->request($path, Request::GET, $query)->getWaitHandle()->join();
        $responseArray = $response->getData();

        $this->assertEquals(1, /* UNSAFE_EXPR */ $responseArray['hits']['total']);
    }

    /**
     * @group unit
     */
    public function testAddHeader() : void
    {
        $client = $this->_getClient();

        // add one header
        $client->addHeader('foo', 'bar');
        $this->assertEquals(array('foo' => 'bar'), $client->getConfigValue('headers'));

        // check class
        $this->assertInstanceOf('Elastica\Client', $client->addHeader('foo', 'bar'));
    }

    /**
     * @group unit
     */
    public function testRemoveHeader() : void
    {
        $client = $this->_getClient();

        // set headers
        $headers = array(
            'first' => 'first value',
            'second' => 'second value',
        );
        foreach ($headers as $key => $value) {
            $client->addHeader($key, $value);
        }
        $this->assertEquals($headers, $client->getConfigValue('headers'));

        // remove one
        $client->removeHeader('first');
        unset($headers['first']);
        $this->assertEquals($headers, $client->getConfigValue('headers'));

        // check class
        $this->assertInstanceOf('Elastica\Client', $client->removeHeader('second'));
    }
}
