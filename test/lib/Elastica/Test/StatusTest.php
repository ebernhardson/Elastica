<?hh
namespace Elastica\Test;

use Elastica\Exception\ResponseException;
use Elastica\Status;
use Elastica\Test\Base as BaseTest;
use Indexish;

class StatusTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGetResponse() : void
    {
        $index = $this->_createIndex();
        $status = Status::create($index->getClient())->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\Response', $status->getResponse());
    }

    /**
     * @group functional
     */
    public function testGetIndexStatuses() : void
    {
        $index = $this->_createIndex();

        $status = Status::create($index->getClient())->getWaitHandle()->join();
        $statuses = $status->getIndexStatuses()->getWaitHandle()->join();

        $this->assertInternalType('array', $statuses);

        foreach ($statuses as $indexStatus) {
            $this->assertInstanceOf('Elastica\Index\Status', $indexStatus);
        }
    }

    /**
     * @group functional
     */
    public function testGetIndexNames() : void
    {
        $indexName = 'test';
        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $index = $this->_createIndex();
        $index->refresh()->getWaitHandle()->join();
        $index->optimize()->getWaitHandle()->join();

        $status = Status::create($index->getClient())->getWaitHandle()->join();
        $names = $status->getIndexNames();

        $this->assertInternalType('array', $names);
        $this->assertContains($index->getName(), $names);

        foreach ($names as $name) {
            $this->assertInternalType('string', $name);
        }
    }

    /**
     * @group functional
     */
    public function testIndexExists() : void
    {
        $indexName = 'elastica_test';
        $aliasName = 'elastica_test-alias';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);

        try {
            // Make sure index is deleted first
            $index->delete()->getWaitHandle()->join();
        } catch (ResponseException $e) {
        }

        $status = Status::create($client)->getWaitHandle()->join();
        $this->assertFalse($status->indexExists($indexName));
        $index->create()->getWaitHandle()->join();

        $status->refresh()->getWaitHandle()->join();
        $this->assertTrue($status->indexExists($indexName));
    }

    /**
     * @group functional
     */
    public function testAliasExists() : void
    {
        $aliasName = 'elastica_test-alias';

        $index1 = $this->_createIndex();
        $indexName = $index1->getName();

        $status = Status::create($index1->getClient())->getWaitHandle()->join();

        foreach ($status->getIndicesWithAlias($aliasName)->getWaitHandle()->join() as $tmpIndex) {
            $tmpIndex->removeAlias($aliasName);
        }

        $this->assertFalse($status->aliasExists($aliasName)->getWaitHandle()->join());

        $index1->addAlias($aliasName)->getWaitHandle()->join();
        $status->refresh()->getWaitHandle()->join();
        $this->assertTrue($status->aliasExists($aliasName)->getWaitHandle()->join());

        $indicesWithAlias = $status->getIndicesWithAlias($aliasName)->getWaitHandle()->join();
        $this->assertEquals(array($indexName), array_map(
            function ($index) {
                return $index->getName();
            }, $indicesWithAlias));
    }

    /**
     * @group functional
     */
    public function testServerStatus() : void
    {
        $client = $this->_getClient();
        $status = $client->getStatus()->getWaitHandle()->join();
        $serverStatus = $status->getServerStatus()->getWaitHandle()->join();

        if (!$serverStatus instanceof Indexish) {
            $this->fail('expected array');
        } else {
            $this->assertTrue(!empty($serverStatus));
            $this->assertTrue('array' == gettype($serverStatus));
            $this->assertArrayHasKey('status', $serverStatus);
            $this->assertTrue($serverStatus['status'] == 200);
            $this->assertArrayHasKey('version', $serverStatus);

            $versionInfo = $serverStatus['version'];
            $this->assertArrayHasKey('number', $versionInfo);
        }
    }
}
