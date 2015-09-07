<?hh
namespace Elastica\Test\Index;

use Elastica\Test\Base as BaseTest;

class StatsTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGetSettings() : void
    {
        $indexName = 'test';

        $client = $this->_getClient();
        $index = $client->getIndex($indexName);
        $index->create(array(), true)->getWaitHandle()->join();
        $stats = $index->getStats()->getWaitHandle()->join();
        $this->assertInstanceOf('Elastica\Index\Stats', $stats);

        $this->assertTrue($stats->getResponse()->isOk());
        $this->assertEquals(0, $stats->get('_all', 'indices', 'test', 'primaries', 'docs', 'count'));
    }
}
