<?hh
namespace Elastica\Test\Index;

use Elastica\Index\Status as IndexStatus;
use Elastica\Test\Base as BaseTest;

class StatusTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGetAliases() : void
    {
        $indexName = 'test';
        $aliasName = 'test-alias';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();

        $status = IndexStatus::create($index)->getWaitHandle()->join();

        $aliases = $status->getAliases()->getWaitHandle()->join();

        $this->assertTrue(empty($aliases));
        $this->assertInternalType('array', $aliases);

        $index->addAlias($aliasName)->getWaitHandle()->join();
        $status->refresh()->getWaitHandle()->join();

        $aliases = $status->getAliases()->getWaitHandle()->join();

        $this->assertTrue(in_array($aliasName, $aliases));
    }

    /**
     * @group functional
     */
    public function testHasAlias() : void
    {
        $indexName = 'test';
        $aliasName = 'test-alias';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();

        $status = IndexStatus::create($index)->getWaitHandle()->join();

        $this->assertFalse($status->hasAlias($aliasName)->getWaitHandle()->join());

        $index->addAlias($aliasName)->getWaitHandle()->join();
        $status->refresh()->getWaitHandle()->join();

        $this->assertTrue($status->hasAlias($aliasName)->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testGetSettings() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $status = $index->getStatus()->getWaitHandle()->join();

        $settings = $status->getSettings()->getWaitHandle()->join();
        $this->assertInternalType('array', $settings);
        $this->assertTrue(isset($settings['index']['number_of_shards']));
    }
}
