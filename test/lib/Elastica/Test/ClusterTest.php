<?hh
namespace Elastica\Test;

use Elastica\Cluster;
use Elastica\Test\Base as BaseTest;

class ClusterTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGetNodeNames() : void
    {
        $client = $this->_getClient();

        $cluster = Cluster::create($client)->getWaitHandle()->join();

        foreach ($cluster->getNodeNames() as $name) {
            $this->assertEquals('Elastica', $name);
        }
    }

    /**
     * @group functional
     */
    public function testGetNodes() : void
    {
        $client = $this->_getClient();
        $cluster = $client->getCluster()->getWaitHandle()->join();

        $nodes = $cluster->getNodes();

        foreach ($nodes as $node) {
            $this->assertInstanceOf('Elastica\Node', $node);
        }

        $this->assertGreaterThan(0, count($nodes));
    }

    /**
     * @group functional
     */
    public function testGetState() : void
    {
        $client = $this->_getClient();
        $cluster = $client->getCluster()->getWaitHandle()->join();
        $state = $cluster->getState();
        $this->assertInternalType('array', $state);
    }

    /**
     * @group functional
     */
    public function testGetIndexNames() : void
    {
        $client = $this->_getClient();
        $cluster = $client->getCluster()->getWaitHandle()->join();

        $index = $this->_createIndex();
        $index->delete()->getWaitHandle()->join();
        $cluster->refresh()->getWaitHandle()->join();

        // Checks that index does not exist
        $indexNames = $cluster->getIndexNames();
        $this->assertNotContains($index->getName(), $indexNames);

        $index = $this->_createIndex();
        $cluster->refresh()->getWaitHandle()->join();

        // Now index should exist
        $indexNames = $cluster->getIndexNames();
        $this->assertContains($index->getName(), $indexNames);
    }

    /**
     * @group functional
     */
    public function testGetHealth() : void
    {
        $client = $this->_getClient();
        $this->assertInstanceOf('Elastica\Cluster\Health', $client->getCluster()->getWaitHandle()->join()->getHealth()->getWaitHandle()->join());
    }
}
