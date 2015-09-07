<?hh
namespace Elastica\Test;

use Elastica\Bulk;
use Elastica\Bulk\Action;
use Elastica\Bulk\Action\AbstractDocument;
use Elastica\Document;
use Elastica\Exception\Bulk\ResponseException;
use Elastica\Exception\NotFoundException;
use Elastica\Filter\Script;
use Elastica\Test\Base as BaseTest;

class BulkTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testSend() : void
    {
        $index = $this->_createIndex();
        $indexName = $index->getName();
        $type = $index->getType('bulk_test');
        $type2 = $index->getType('bulk_test2');
        $client = $index->getClient();

        $newDocument1 = $type->createDocument('1', array('name' => 'Mister Fantastic'));
        $newDocument2 = new Document('2', array('name' => 'Invisible Woman'));
        $newDocument3 = $type->createDocument('3', array('name' => 'The Human Torch'));
        $newDocument4 = $type->createDocument(null, array('name' => 'The Thing'));

        $newDocument3->setOpType(Document::OP_TYPE_CREATE);

        $documents = array(
            $newDocument1,
            $newDocument2,
            $newDocument3,
            $newDocument4,
        );

        $bulk = new Bulk($client);
        $bulk->setType($type2);
        $bulk->addDocuments($documents);

        $actions = $bulk->getActions();

        $action = $actions[0];
        $this->assertInstanceOf('Elastica\Bulk\Action\IndexDocument', $action);
        if ($action instanceof \Elastica\Bulk\Action\IndexDocument) {
            $this->assertEquals('index', $action->getOpType());
            $this->assertSame($newDocument1, $action->getDocument());
        }

        $action = $actions[1];
        $this->assertInstanceOf('Elastica\Bulk\Action\IndexDocument', $action);
        if ($action instanceof \Elastica\Bulk\Action\IndexDocument) {
            $this->assertEquals('index', $action->getOpType());
            $this->assertSame($newDocument2, $action->getDocument());
        }

        $action = $actions[2];
        $this->assertInstanceOf('Elastica\Bulk\Action\CreateDocument', $action);
        if ($action instanceof \Elastica\Bulk\Action\CreateDocument) {
            $this->assertEquals('create', $action->getOpType());
            $this->assertSame($newDocument3, $action->getDocument());
        }

        $action = $actions[3];
        $this->assertInstanceOf('Elastica\Bulk\Action\IndexDocument', $action);
        if ($action instanceof \Elastica\Bulk\Action\IndexDocument) {
            $this->assertEquals('index', $action->getOpType());
            $this->assertSame($newDocument4, $action->getDocument());
        }

        $data = $bulk->toArray();

        $expected = array(
            array('index' => Map {'_index' => $indexName, '_type' => 'bulk_test', '_id' => 1}),
            array('name' => 'Mister Fantastic'),
            array('index' => Map {'_id' => 2}),
            array('name' => 'Invisible Woman'),
            array('create' => Map {'_index' => $indexName, '_type' => 'bulk_test', '_id' => 3}),
            array('name' => 'The Human Torch'),
            array('index' => Map {'_index' => $indexName, '_type' => 'bulk_test'}),
            array('name' => 'The Thing'),
        );
        $this->assertEquals($expected, $data);

        $expected = '{"index":{"_index":"'.$indexName.'","_type":"bulk_test","_id":"1"}}
{"name":"Mister Fantastic"}
{"index":{"_id":"2"}}
{"name":"Invisible Woman"}
{"create":{"_index":"'.$indexName.'","_type":"bulk_test","_id":"3"}}
{"name":"The Human Torch"}
{"index":{"_index":"'.$indexName.'","_type":"bulk_test"}}
{"name":"The Thing"}
';

        $expected = str_replace(PHP_EOL, "\n", $expected);
        $this->assertEquals($expected, (string) str_replace(PHP_EOL, "\n", (string) $bulk));

        $response = $bulk->send()->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Bulk\ResponseSet', $response);

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        foreach ($response as $i => $bulkResponse) {
            $this->assertInstanceOf('Elastica\Bulk\Response', $bulkResponse);
            $this->assertTrue($bulkResponse->isOk());
            $this->assertFalse($bulkResponse->hasError());
            $this->assertSame($actions[$i], $bulkResponse->getAction());
        }

        $type->getIndex()->refresh()->getWaitHandle()->join();
        $type2->getIndex()->refresh()->getWaitHandle()->join();

        $this->assertEquals(3, $type->count()->getWaitHandle()->join());
        $this->assertEquals(1, $type2->count()->getWaitHandle()->join());

        $bulk = new Bulk($client);
        $bulk->addDocument($newDocument3, Action::OP_TYPE_DELETE);

        $data = $bulk->toArray();

        $expected = array(
            array('delete' => Map {'_index' => $indexName, '_type' => 'bulk_test', '_id' => 3}),
        );
        $this->assertEquals($expected, $data);

        $bulk->send()->getWaitHandle()->join();

        $type->getIndex()->refresh()->getWaitHandle()->join();

        $this->assertEquals(2, $type->count()->getWaitHandle()->join());

        try {
            $type->getDocument('3')->getWaitHandle()->join();
            $this->fail('Document #3 should be deleted');
        } catch (NotFoundException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @group functional
     */
    public function testUnicodeBulkSend() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('bulk_test');
        $type2 = $index->getType('bulk_test2');
        $client = $index->getClient();

        $newDocument1 = $type->createDocument('1', array('name' => 'Сегодня, я вижу, особенно грустен твой взгляд,'));
        $newDocument2 = new Document('2', array('name' => 'И руки особенно тонки, колени обняв.'));
        $newDocument3 = $type->createDocument('3', array('name' => 'Послушай: далеко, далеко, на озере Чад / Изысканный бродит жираф.'));

        $documents = array(
            $newDocument1,
            $newDocument2,
            $newDocument3,
        );

        $bulk = new Bulk($client);
        $bulk->setType($type2);
        $bulk->addDocuments($documents);

        $actions = $bulk->getActions();

        $assert = function(\Elastica\Document $newDocument, Action $action) {
            $this->assertInstanceOf('\Elastica\Bulk\Action\AbstractDocument', $action);
            if ($action instanceof \Elastica\Bulk\Action\AbstractDocument) {
                $this->assertSame($newDocument, $action->getDocument());
            }
        };
        $assert($newDocument1, $actions[0]);
        $assert($newDocument2, $actions[1]);
        $assert($newDocument3, $actions[2]);
    }

    /**
     * @group functional
     */
    public function testSetIndexType() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('index');
        $type = $index->getType('type');

        $index2 = $client->getIndex('index2');
        $type2 = $index2->getType('type2');

        $bulk = new Bulk($client);

        $this->assertFalse($bulk->hasIndex());
        $this->assertFalse($bulk->hasType());

        $bulk->setIndex($index);
        $this->assertTrue($bulk->hasIndex());
        $this->assertFalse($bulk->hasType());
        $this->assertEquals('index', $bulk->getIndex());

        $bulk->setType($type2);
        $this->assertTrue($bulk->hasIndex());
        $this->assertTrue($bulk->hasType());
        $this->assertEquals('index2', $bulk->getIndex());
        $this->assertEquals('type2', $bulk->getType());

        $bulk->setType($type);
        $this->assertTrue($bulk->hasIndex());
        $this->assertTrue($bulk->hasType());
        $this->assertEquals('index', $bulk->getIndex());
        $this->assertEquals('type', $bulk->getType());

        $bulk->setIndex($index2);
        $this->assertTrue($bulk->hasIndex());
        $this->assertTrue($bulk->hasType());
        $this->assertEquals('index2', $bulk->getIndex());
        $this->assertEquals('type', $bulk->getType());
    }

    /**
     * @group unit
     */
    public function testAddActions() : void
    {
        $client = $this->_getClient();
        $bulk = new Bulk($client);

        $action1 = new Action(Action::OP_TYPE_DELETE);
        $action1->setIndex('index');
        $action1->setType('type');
        $action1->setId('1');

        $action2 = new Action(Action::OP_TYPE_INDEX);
        $action2->setIndex('index');
        $action2->setType('type');
        $action2->setId('1');
        $action2->setSource(array('name' => 'Batman'));

        $actions = array(
            $action1,
            $action2,
        );

        $bulk->addActions($actions);

        $getActions = $bulk->getActions();

        $this->assertSame($action1, $getActions[0]);
        $this->assertSame($action2, $getActions[1]);
    }

    /**
     * @group unit
     */
    public function testAddRawData() : void
    {
        $bulk = new Bulk($this->_getClient());

        $rawData = array(
            array('index' => array('_index' => 'test', '_type' => 'user', '_id' => '1')),
            array('user' => array('name' => 'hans')),
            array('delete' => array('_index' => 'test', '_type' => 'user', '_id' => '2')),
            array('delete' => array('_index' => 'test', '_type' => 'user', '_id' => '3')),
            array('create' => array('_index' => 'test', '_type' => 'user', '_id' => '4')),
            array('user' => array('name' => 'mans')),
            array('delete' => array('_index' => 'test', '_type' => 'user', '_id' => '5')),
        );

        $bulk->addRawData($rawData);

        $actions = $bulk->getActions();

        $this->assertInternalType('array', $actions);
        $this->assertEquals(5, count($actions));

        $this->assertInstanceOf('Elastica\Bulk\Action', $actions[0]);
        $this->assertEquals('index', $actions[0]->getOpType());
        $this->assertEquals($rawData[0]['index'], $actions[0]->getMetadata());
        $this->assertTrue($actions[0]->hasSource());
        $this->assertEquals($rawData[1], $actions[0]->getSource());

        $this->assertInstanceOf('Elastica\Bulk\Action', $actions[1]);
        $this->assertEquals('delete', $actions[1]->getOpType());
        $this->assertEquals($rawData[2]['delete'], $actions[1]->getMetadata());
        $this->assertFalse($actions[1]->hasSource());

        $this->assertInstanceOf('Elastica\Bulk\Action', $actions[2]);
        $this->assertEquals('delete', $actions[2]->getOpType());
        $this->assertEquals($rawData[3]['delete'], $actions[2]->getMetadata());
        $this->assertFalse($actions[2]->hasSource());

        $this->assertInstanceOf('Elastica\Bulk\Action', $actions[3]);
        $this->assertEquals('create', $actions[3]->getOpType());
        $this->assertEquals($rawData[4]['create'], $actions[3]->getMetadata());
        $this->assertTrue($actions[3]->hasSource());
        $this->assertEquals($rawData[5], $actions[3]->getSource());

        $this->assertInstanceOf('Elastica\Bulk\Action', $actions[4]);
        $this->assertEquals('delete', $actions[4]->getOpType());
        $this->assertEquals($rawData[6]['delete'], $actions[4]->getMetadata());
        $this->assertFalse($actions[4]->hasSource());
    }

    /**
     * @group unit
     * @dataProvider invalidRawDataProvider
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testInvalidRawData($rawData, $failMessage) : void
    {
        $bulk = new Bulk($this->_getClient());

        $bulk->addRawData($rawData);

        $this->fail($failMessage);
    }

    public function invalidRawDataProvider() : array<array>
    {
        return array(
            array(
                array(
                    array('index' => array('_index' => 'test', '_type' => 'user', '_id' => '1')),
                    array('user' => array('name' => 'hans')),
                    array('user' => array('name' => 'mans')),
                ),
                'Two sources for one action',
            ),
            array(
                array(
                    array('index' => array('_index' => 'test', '_type' => 'user', '_id' => '1')),
                    array('user' => array('name' => 'hans')),
                    array('upsert' => array('_index' => 'test', '_type' => 'user', '_id' => '2')),
                ),
                'Invalid optype for action',
            ),
            array(
                array(
                    array('user' => array('name' => 'mans')),
                ),
                'Source without action',
            ),
            array(
                array(
                    array(),
                ),
                'Empty array',
            ),
            array(
                array(
                    'dummy',
                ),
                'String as data',
            ),
        );
    }

    /**
     * @group functional
     */
    public function testErrorRequest() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('bulk_test');
        $client = $index->getClient();

        $documents = array(
            $type->createDocument('1', array('name' => 'Mister Fantastic')),
            $type->createDocument('2', array('name' => 'Invisible Woman')),
            $type->createDocument('2', array('name' => 'The Human Torch')),
        );

        $documents[2]->setOpType(Document::OP_TYPE_CREATE);

        $bulk = new Bulk($client);
        $bulk->addDocuments($documents);

        try {
            $bulk->send()->getWaitHandle()->join();
            $this->fail('3rd document create should produce error');
        } catch (ResponseException $e) {
            $this->assertContains('DocumentAlreadyExists', $e->getMessage());
            $failures = $e->getFailures();
            $this->assertInternalType('array', $failures);
            $this->assertArrayHasKey(0, $failures);
            $this->assertContains('DocumentAlreadyExists', $failures[0]);
        }
    }

    /**
     * @group functional
     */
    public function testRawDocumentDataRequest() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('bulk_test');
        $client = $index->getClient();

        $documents = array(
            new Document(null, '{"name":"Mister Fantastic"}'),
            new Document(null, '{"name":"Invisible Woman"}'),
            new Document(null, '{"name":"The Human Torch"}'),
        );

        $bulk = new Bulk($client);
        $bulk->addDocuments($documents);
        $bulk->setType($type);

        $expectedJson = '{"index":{}}
{"name":"Mister Fantastic"}
{"index":{}}
{"name":"Invisible Woman"}
{"index":{}}
{"name":"The Human Torch"}
';
        $expectedJson = str_replace(PHP_EOL, "\n", $expectedJson);
        $this->assertEquals($expectedJson, $bulk->toString());

        $response = $bulk->send()->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $type->getIndex()->refresh()->getWaitHandle()->join();

        $response = $type->search()->getWaitHandle()->join();
        $this->assertEquals(3, $response->count());

        foreach (array('Mister', 'Invisible', 'Torch') as $name) {
            $result = $type->search($name)->getWaitHandle()->join();
            $this->assertEquals(1, count($result->getResults()), $name);
        }
    }

    /**
     * @group functional
     * @dataProvider udpDataProvider
     */
    public function testUdp($clientConfig, $host, $port, @bool $shouldFail = false) : void
    {
        if (!function_exists('socket_create')) {
            $this->markTestSkipped('Function socket_create() does not exist.');
        }
        $client = $this->_getClient($clientConfig);
        $index = $client->getIndex('elastica_test');
        $index->create(array('index' => array('number_of_shards' => 1, 'number_of_replicas' => 0)), true)->getWaitHandle()->join();
        $type = $index->getType('udp_test');
        $client = $index->getClient();

        $type->setMapping(array('name' => array('type' => 'string')))->getWaitHandle()->join();

        $docs = array(
            $type->createDocument('1', array('name' => 'Mister Fantastic')),
            $type->createDocument('2', array('name' => 'Invisible Woman')),
            $type->createDocument('3', array('name' => 'The Human Torch')),
            $type->createDocument('4', array('name' => 'The Thing')),
            $type->createDocument('5', array('name' => 'Mole Man')),
            $type->createDocument('6', array('name' => 'The Skrulls')),
        );

        $bulk = new Bulk($client);
        $bulk->addDocuments($docs);

        $bulk->sendUdp($host, $port);

        $i = 0;
        $limit = 20;

        // adds 6 documents and checks if on average every document is added in less then 0.2 seconds
        do {
            usleep(200000);    // 0.2 seconds
        } while ($type->count()->getWaitHandle()->join() < 6 && ++$i < $limit);

        if ($shouldFail) {
            $this->assertEquals($limit, $i, 'Invalid udp connection data. Test should fail');
        } else {
            $this->assertLessThan($limit, $i, 'It took too much time waiting for UDP request result');

            foreach ($docs as $doc) {
                $id = $doc->getId();
                if ($id === null) {
                    continue;
                }
                $getDoc = $type->getDocument($id)->getWaitHandle()->join();
                $this->assertEquals($doc->getData(), $getDoc->getData());
            }
        }
    }

    /**
     * @group functional
     */
    public function testUpdate() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('bulk_test');
        $client = $index->getClient();

        $doc1 = $type->createDocument('1', array('name' => 'John'));
        $doc2 = $type->createDocument('2', array('name' => 'Paul'));
        $doc3 = $type->createDocument('3', array('name' => 'George'));
        $doc4 = $type->createDocument('4', array('name' => 'Ringo'));
        $documents = array($doc1, $doc2, $doc3, $doc4);

        //index some documents
        $bulk = new Bulk($client);
        $bulk->setType($type);
        $bulk->addDocuments($documents);
        $response = $bulk->send()->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $index->refresh()->getWaitHandle()->join();

        //test updating via document
        $doc2 = $type->createDocument('2', array('name' => 'The Walrus'));
        $bulk = new Bulk($client);
        $bulk->setType($type);
        $updateAction = new \Elastica\Bulk\Action\UpdateDocument($doc2);
        $bulk->addAction($updateAction);
        $response = $bulk->send()->getWaitHandle()->join();

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $index->refresh()->getWaitHandle()->join();

        $doc = $type->getDocument('2')->getWaitHandle()->join();
        $docData = $doc->getData();
        $this->assertEquals('The Walrus', /* UNSAFE_EXPR */ $docData['name']);

        //test updating via script
        $script = new \Elastica\Script('ctx._source.name += param1;', Map {'param1' => ' was Paul'}, null, '2');
        $doc2 = new Document();
        $script->setUpsert($doc2);
        $updateAction = Action\AbstractDocument::create($script, Action::OP_TYPE_UPDATE);
        $bulk = new Bulk($client);
        $bulk->setType($type);
        $bulk->addAction($updateAction);
        $response = $bulk->send()->getWaitHandle()->join();;

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $index->refresh()->getWaitHandle()->join();

        $doc2 = $type->getDocument('2')->getWaitHandle()->join();
        $this->assertEquals('The Walrus was Paul', $doc2->get('name'));

        //test upsert
        $script = new \Elastica\Script('ctx._scource.counter += count', Map {'count' => 1}, null, '5');
        $doc = new Document('', Map {'counter' => 1});
        $script->setUpsert($doc);
        $updateAction = Action\AbstractDocument::create($script, Action::OP_TYPE_UPDATE);
        $bulk = new Bulk($client);
        $bulk->setType($type);
        $bulk->addAction($updateAction);
        $response = $bulk->send()->getWaitHandle()->join();;

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $index->refresh()->getWaitHandle()->join();
        $doc = $type->getDocument('5')->getWaitHandle()->join();
        $this->assertEquals(1, $doc->get('counter'));

        //test doc_as_upsert
        $doc = new \Elastica\Document('6', array('test' => 'test'));
        $doc->setDocAsUpsert(true);
        $updateAction = Action\AbstractDocument::create($doc, Action::OP_TYPE_UPDATE);
        $bulk = new Bulk($client);
        $bulk->setType($type);
        $bulk->addAction($updateAction);
        $response = $bulk->send()->getWaitHandle()->join();;

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $index->refresh()->getWaitHandle()->join();
        $doc = $type->getDocument('6')->getWaitHandle()->join();
        $this->assertEquals('test', $doc->get('test'));

        //test doc_as_upsert with set of documents (use of addDocuments)
        $doc1 = new \Elastica\Document('7', array('test' => 'test1'));
        $doc1->setDocAsUpsert(true);
        $doc2 = new \Elastica\Document('8', array('test' => 'test2'));
        $doc2->setDocAsUpsert(true);
        $docs = array($doc1, $doc2);
        $bulk = new Bulk($client);
        $bulk->setType($type);
        $bulk->addDocuments($docs, \Elastica\Bulk\Action::OP_TYPE_UPDATE);
        $response = $bulk->send()->getWaitHandle()->join();;

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $index->refresh()->getWaitHandle()->join();
        $doc = $type->getDocument('7')->getWaitHandle()->join();
        $this->assertEquals('test1', $doc->get('test'));
        $doc = $type->getDocument('8')->getWaitHandle()->join();
        $this->assertEquals('test2', $doc->get('test'));

        //test updating via document with json string as data
        $doc3 = $type->createDocument('2');
        $bulk = new Bulk($client);
        $bulk->setType($type);
        $doc3->setData('{"name" : "Paul it is"}');
        $updateAction = new \Elastica\Bulk\Action\UpdateDocument($doc3);
        $bulk->addAction($updateAction);
        $response = $bulk->send()->getWaitHandle()->join();;

        $this->assertTrue($response->isOk());
        $this->assertFalse($response->hasError());

        $index->refresh()->getWaitHandle()->join();

        $doc = $type->getDocument('2')->getWaitHandle()->join();
        $docData = $doc->getData();
        $this->assertEquals('Paul it is', /* UNSAFE_EXPR */ $docData['name']);

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group unit
     */
    public function testGetPath() : void
    {
        $client = $this->_getClient();
        $bulk = new Bulk($client);

        $this->assertEquals('_bulk', $bulk->getPath());

        $indexName = 'testIndex';

        $bulk->setIndex($indexName);
        $this->assertEquals($indexName.'/_bulk', $bulk->getPath());

        $typeName = 'testType';
        $bulk->setType($typeName);
        $this->assertEquals($indexName.'/'.$typeName.'/_bulk', $bulk->getPath());
    }

    /**
     * @group functional
     */
    public function testRetry() : void
    {
        $index = $this->_createIndex();
        $type = $index->getType('bulk_test');
        $client = $index->getClient();

        $doc1 = $type->createDocument('1', array('name' => 'Mister Fantastic'));
        $doc1->setOpType(Action::OP_TYPE_UPDATE);
        $doc1->setRetryOnConflict(5);

        $bulk = new Bulk($client);
        $bulk->addDocument($doc1);

        $actions = $bulk->getActions();

        $metadata = $actions[0]->getMetadata();
        $this->assertEquals(5, $metadata[ '_retry_on_conflict' ]);

        $script = new \Elastica\Script('');
        $script->setRetryOnConflict(5);

        $bulk = new Bulk($client);
        $bulk->addScript($script);

        $actions = $bulk->getActions();

        $metadata = $actions[0]->getMetadata();
        $this->assertEquals(5, $metadata[ '_retry_on_conflict' ]);
    }

    /**
     * @group unit
     */
    public function testSetShardTimeout() : void
    {
        $bulk = new Bulk($this->_getClient());
        $this->assertInstanceOf('Elastica\Bulk', $bulk->setShardTimeout('10'));
    }

    /**
     * @group unit
     */
    public function testSetRequestParam() : void
    {
        $bulk = new Bulk($this->_getClient());
        $this->assertInstanceOf('Elastica\Bulk', $bulk->setRequestParam('key', 'value'));
    }

    /**
     * @group benchmark
     */
    public function testMemoryUsage() : void
    {
        $type = $this->_createIndex()->getType('test');

        $data = array(
            'text1' => 'Very long text for a string',
            'text2' => 'But this is not very long',
            'text3' => 'random or not random?',
        );

        $startMemory = memory_get_usage();

        for ($n = 1; $n < 10; ++$n) {
            $docs = array();

            for ($i = 1; $i <= 3000; ++$i) {
                $docs[] = new Document(uniqid(), $data);
            }

            $type->addDocuments($docs)->getWaitHandle()->join();
            $docs = array();
        }

        $endMemory = memory_get_usage();

        $this->assertLessThan(1.3, $endMemory / $startMemory);
    }

    public function udpDataProvider() : array<array>
    {
        return array(
            array(
                array(),
                $this->_getHost(),
                9700,
            ),
            array(
                array(),
                $this->_getHost(),
                9700,
            ),
            array(
                array(
                    'udp' => array(
                        'host' => $this->_getHost(),
                        'port' => 9700,
                    ),
                ),
                null,
                null,
            ),
            array(
                array(
                    'udp' => array(
                        'host' => $this->_getHost(),
                        'port' => 9800,
                    ),
                ),
                $this->_getHost(),
                9700,
            ),
            array(
                array(
                    'udp' => array(
                        'host' => $this->_getHost(),
                        'port' => 9800,
                    ),
                ),
                null,
                null,
                true,
            ),
            array(
                array(),
                $this->_getHost(),
                9800,
                true,
            ),
        );
    }
}
