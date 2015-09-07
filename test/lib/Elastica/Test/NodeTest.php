<?hh
namespace Elastica\Test;

use Elastica\Node;
use Elastica\Test\Base as BaseTest;

class NodeTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testCreateNode() : void
    {
        $client = $this->_getClient();
        $names = $client->getCluster()->getWaitHandle()->join()->getNodeNames();
        $name = reset($names);

        $node = new Node($name, $client);
        $this->assertInstanceOf('Elastica\Node', $node);
    }

    /**
     * @group functional
     */
    public function testGetInfo() : void
    {
        $client = $this->_getClient();
        $names = $client->getCluster()->getWaitHandle()->join()->getNodeNames();
        $name = reset($names);

        $node = new Node($name, $client);

        $info = $node->getInfo()->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Node\Info', $info);
    }

    /**
     * @group functional
     */
    public function testGetStats() : void
    {
        $client = $this->_getClient();
        $names = $client->getCluster()->getWaitHandle()->join()->getNodeNames();
        $name = reset($names);

        $node = new Node($name, $client);

        $stats = $node->getStats()->getWaitHandle()->join();

        $this->assertInstanceOf('Elastica\Node\Stats', $stats);
    }

    /**
     * @group functional
     */
    public function testGetName() : void
    {
        $nodes = $this->_getClient()->getCluster()->getWaitHandle()->join()->getNodes();
        // At least 1 instance must exist
        $this->assertGreaterThan(0, $nodes);

        foreach ($nodes as $node) {
            $this->assertEquals($node->getName()->getWaitHandle()->join(), 'Elastica');
        }
    }

    /**
     * @group functional
     */
    public function testGetId() : void
    {
        $node = new Node('Elastica', $this->_getClient());
    }
}
