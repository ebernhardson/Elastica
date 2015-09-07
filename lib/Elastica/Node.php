<?hh
namespace Elastica;

use Elastica\Node\Info;
use Elastica\Node\Stats;

/**
 * Elastica cluster node object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Node
{
    /**
     * Client.
     *
     * @var \Elastica\Client
     */
    protected Client $_client;

    /**
     * @var string Unique node id
     */
    protected string $_id;

    /**
     * Node name.
     *
     * @var string Node name
     */
    protected string $_name = '';

    /**
     * Node stats.
     *
     * @var \Elastica\Node\Stats Node Stats
     */
    protected ?Stats $_stats = null;

    /**
     * Node info.
     *
     * @var \Elastica\Node\Info Node info
     */
    protected ?Info $_info = null;

    /**
     * Create a new node object.
     *
     * @param string           $id     Node id or name
     * @param \Elastica\Client $client Node object
     */
    public function __construct(string $id, Client $client)
    {
        $this->_client = $client;
        $this->_id = $id;
    }

    /**
     * @return string Unique node id. Can also be name if id not exists.
     */
    public function getId() : string
    {
        return $this->_id;
    }

    /**
     * @param string $id Node id
     *
     * @return $this Refreshed object
     */
    public function setId(string $id) : Node
    {
        $this->_id = $id;

        return $this->refresh();
    }

    /**
     * Get the name of the node.
     *
     * @return Awaitable<string> Node name
     */
    public async function getName() : Awaitable<string>
    {
        if (empty($this->_name)) {
            $info = await $this->getInfo();
            $this->_name = $info->getName();
        }

        return $this->_name;
    }

    /**
     * Returns the current client object.
     *
     * @return \Elastica\Client Client
     */
    public function getClient() : Client
    {
        return $this->_client;
    }

    /**
     * Return stats object of the current node.
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-stats.html
     *
     * @return Awaitable<\Elastica\Node\Stats> Node stats
     */
    public async function getStats() : Awaitable<Stats>
    {
        if (!$this->_stats) {
            $this->_stats = await Stats::create($this);
        }

        return $this->_stats;
    }

    /**
     * Return info object of the current node.
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-info.html
     *
     * @return Awaitable<\Elastica\Node\Info> Node info object
     */
    public async function getInfo() : Awaitable<Info>
    {
        if (!$this->_info) {
            $this->_info = await Info::create($this);
        }

        return $this->_info;
    }

    /**
     * Refreshes all node information.
     *
     * This should be called after updating a node to refresh all information
     *
     * @return Node
     */
    public function refresh() : Node
    {
        $this->_stats = null;
        $this->_info = null;
        return $this;
    }

    /**
     * Shuts this node down.
     *
     * @param string $delay OPTIONAL Delay after which node is shut down (default = 1s)
     *
     * @return Awaitable<\Elastica\Response>
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-shutdown.html
     */
    public function shutdown(string $delay = '1s') : Awaitable<Response>
    {
        $path = '_cluster/nodes/'.$this->getId().'/_shutdown?delay='.$delay;

        return $this->_client->request($path, Request::POST);
    }
}
