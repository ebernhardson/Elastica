<?hh
namespace Elastica\Test\Node;

use Elastica\Node;
use Elastica\Node\Info as NodeInfo;
use Elastica\Test\Base as BaseTest;

class InfoTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGet() : void
    {
        $client = $this->_getClient();
        $names = $client->getCluster()->getWaitHandle()->join()->getNodeNames();
        $name = reset($names);

        $node = new Node($name, $client);
        $info = NodeInfo::create($node)->getWaitHandle()->join();

        $this->assertNull($info->get('os', 'mem', 'total'));

        // Load os infos
        $info = NodeInfo::create($node, array('os'))->getWaitHandle()->join();

        $this->assertNotNull($info->get('os', 'mem', 'total_in_bytes'));
        $this->assertInternalType('array', $info->get('os', 'mem'));
        $this->assertNull($info->get('test', 'notest', 'notexist'));
    }

    /**
     * @group functional
     */
    public function testHasPlugin() : void
    {
        $client = $this->_getClient();
        $nodes = $client->getCluster()->getWaitHandle()->join()->getNodes();
        $node = $nodes[0];
        $info = $node->getInfo()->getWaitHandle()->join();

        $pluginName = 'mapper-attachments';

        $this->assertTrue($info->hasPlugin($pluginName)->getWaitHandle()->join());
        $this->assertFalse($info->hasPlugin('foo')->getWaitHandle()->join());
    }

    /**
     * @group functional
     */
    public function testGetId() : void
    {
        $client = $this->_getClient();
        $nodes = $client->getCluster()->getWaitHandle()->join()->getNodes();

        $ids = array();

        foreach ($nodes as $node) {
            $id = $node->getInfo()->getWaitHandle()->join()->getId();

            // Checks that the ids are unique
            $this->assertFalse(in_array($id, $ids));
            $ids[] = $id;
        }
    }

    /**
     * @group functional
     */
    public function testGetName() : void
    {
        $client = $this->_getClient();
        $nodes = $client->getCluster()->getWaitHandle()->join()->getNodes();

        foreach ($nodes as $node) {
            $this->assertEquals('Elastica', $node->getInfo()->getWaitHandle()->join()->getName());
        }
    }
}
