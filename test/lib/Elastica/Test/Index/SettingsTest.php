<?hh
namespace Elastica\Test\Index;

use Elastica\Document;
use Elastica\Exception\ResponseException;
use Elastica\Index;
use Elastica\Index\Settings as IndexSettings;
use Elastica\Test\Base as BaseTest;

class SettingsTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGet() : void
    {
        $indexName = 'elasticatest';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();
        $settings = $index->getSettings();

        $this->assertNotNull($settings->get('number_of_replicas')->getWaitHandle()->join());
        $this->assertNotNull($settings->get('number_of_shards')->getWaitHandle()->join());
        $this->assertNull($settings->get('kjqwerjlqwer')->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testGetWithAlias() : void
    {
        $indexName = 'elasticatest';
        $aliasName = 'elasticatest_alias';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $index->addAlias($aliasName)->getWaitHandle()->join();
        $index = $client->getIndex($aliasName);
        $settings = $index->getSettings();

        $this->assertNotNull($settings->get('number_of_replicas')->getWaitHandle()->join());
        $this->assertNotNull($settings->get('number_of_shards')->getWaitHandle()->join());
        $this->assertNull($settings->get('kjqwerjlqwer')->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSetNumberOfReplicas() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $settings = $index->getSettings();

        $settings->setNumberOfReplicas(2)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();
        $this->assertEquals(2, $settings->get('number_of_replicas')->getWaitHandle()->join());

        $settings->setNumberOfReplicas(3)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();
        $this->assertEquals(3, $settings->get('number_of_replicas')->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSetRefreshInterval() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();

        $settings = $index->getSettings();

        $settings->setRefreshInterval('2s')->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();
        $this->assertEquals('2s', $settings->get('refresh_interval')->getWaitHandle()->join());

        $settings->setRefreshInterval('5s')->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();
        $this->assertEquals('5s', $settings->get('refresh_interval')->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testGetRefreshInterval() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();

        $settings = $index->getSettings();

        $this->assertEquals(IndexSettings::DEFAULT_REFRESH_INTERVAL, $settings->getRefreshInterval()->getWaitHandle()->join());

        $interval = '2s';
        $settings->setRefreshInterval($interval)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();
        $this->assertEquals($interval, $settings->getRefreshInterval()->getWaitHandle()->join());
        $this->assertEquals($interval, $settings->get('refresh_interval')->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSetMergePolicy() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        //wait for the shards to be allocated
        $this->_waitForAllocation($index);

        $settings = $index->getSettings();

        $settings->setMergePolicy('expunge_deletes_allowed', '15')->getWaitHandle()->join();
        $this->assertEquals(15, $settings->getMergePolicy('expunge_deletes_allowed')->getWaitHandle()->join());

        $settings->setMergePolicy('expunge_deletes_allowed', '10')->getWaitHandle()->join();
        $this->assertEquals(10, $settings->getMergePolicy('expunge_deletes_allowed')->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSetMergeFactor() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();

        //wait for the shards to be allocated
        $this->_waitForAllocation($index);

        $settings = $index->getSettings();

        $response = $settings->setMergePolicy('merge_factor', '15')->getWaitHandle()->join();
        $this->assertEquals(15, $settings->getMergePolicy('merge_factor')->getWaitHandle()->join());
        $this->assertInstanceOf('Elastica\Response', $response);
        $this->assertTrue($response->isOk());

        $settings->setMergePolicy('merge_factor', '10')->getWaitHandle()->join();
        $this->assertEquals(10, $settings->getMergePolicy('merge_factor')->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSetMergePolicyType() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();

        //wait for the shards to be allocated
        $this->_waitForAllocation($index);

        $settings = $index->getSettings();

        $settings->setMergePolicyType('log_byte_size')->getWaitHandle()->join();
        $this->assertEquals('log_byte_size', $settings->getMergePolicyType()->getWaitHandle()->join());

        $response = $settings->setMergePolicy('merge_factor', '15')->getWaitHandle()->join();
        $this->assertEquals(15, $settings->getMergePolicy('merge_factor')->getWaitHandle()->join());
        $this->assertInstanceOf('Elastica\Response', $response);
        $this->assertTrue($response->isOk());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testSetReadOnly() : void
    {
        $index = $this->_createIndex();
        //wait for the shards to be allocated
        $this->_waitForAllocation($index);
        $index->getSettings()->setReadOnly(false)->getWaitHandle()->join();

        // Add document to normal index
        $doc1 = new Document(null, array('hello' => 'world'));
        $doc2 = new Document(null, array('hello' => 'world'));
        $doc3 = new Document(null, array('hello' => 'world'));

        $type = $index->getType('test');
        $type->addDocument($doc1)->getWaitHandle()->join();
        $this->assertFalse($index->getSettings()->getReadOnly()->getWaitHandle()->join());

        // Try to add doc to read only index
        $index->getSettings()->setReadOnly(true)->getWaitHandle()->join();
        $this->assertTrue($index->getSettings()->getReadOnly()->getWaitHandle()->join());

        try {
            $type->addDocument($doc2)->getWaitHandle()->join();
            $this->fail('Should throw exception because of read only');
        } catch (ResponseException $e) {
            $message = $e->getMessage();
            $this->assertContains('ClusterBlockException', $message);
            $this->assertContains('index write', $message);
        }

        // Remove read only, add document
        $response = $index->getSettings()->setReadOnly(false)->getWaitHandle()->join();
        $this->assertTrue($response->isOk());

        $type->addDocument($doc3)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $this->assertEquals(2, $type->count()->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testGetSetBlocksRead() : void
    {
        $index = $this->_createIndex();
        $index->refresh()->getWaitHandle()->join();
        $settings = $index->getSettings();

        $this->assertFalse($settings->getBlocksRead()->getWaitHandle()->join());

        $settings->setBlocksRead(true)->getWaitHandle()->join();
        $this->assertTrue($settings->getBlocksRead()->getWaitHandle()->join());

        $settings->setBlocksRead(false)->getWaitHandle()->join();
        $this->assertFalse($settings->getBlocksRead()->getWaitHandle()->join());

        $settings->setBlocksRead()->getWaitHandle()->join();
        $this->assertTrue($settings->getBlocksRead()->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testGetSetBlocksWrite() : void
    {
        $index = $this->_createIndex();
        $index->refresh()->getWaitHandle()->join();
        $settings = $index->getSettings();

        $this->assertFalse($settings->getBlocksWrite()->getWaitHandle()->join());

        $settings->setBlocksWrite(true)->getWaitHandle()->join();
        $this->assertTrue($settings->getBlocksWrite()->getWaitHandle()->join());

        $settings->setBlocksWrite(false)->getWaitHandle()->join();
        $this->assertFalse($settings->getBlocksWrite()->getWaitHandle()->join());

        $settings->setBlocksWrite()->getWaitHandle()->join();
        $this->assertTrue($settings->getBlocksWrite()->getWaitHandle()->join());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testGetSetBlocksMetadata() : void
    {
        $index = $this->_createIndex();
        $index->refresh()->getWaitHandle()->join();
        $settings = $index->getSettings();

        $this->assertFalse($settings->getBlocksMetadata()->getWaitHandle()->join());

        $settings->setBlocksMetadata(true)->getWaitHandle()->join();
        $this->assertTrue($settings->getBlocksMetadata()->getWaitHandle()->join());

        $settings->setBlocksMetadata(false)->getWaitHandle()->join();
        $this->assertFalse($settings->getBlocksMetadata()->getWaitHandle()->join());

        $settings->setBlocksMetadata()->getWaitHandle()->join();
        $this->assertTrue($settings->getBlocksMetadata()->getWaitHandle()->join());

        $settings->setBlocksMetadata(false)->getWaitHandle()->join(); // Cannot delete index otherwise
        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group functional
     */
    public function testNotFoundIndex() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('not_found_index');
        //wait for the shards to be allocated

        try {
            $settings = $index->getSettings()->getAll()->getWaitHandle()->join();
            $this->fail('Should throw exception because of index not found');
        } catch (ResponseException $e) {
            $message = $e->getMessage();
            $this->assertContains('IndexMissingException', $message);
        }
    }
}
