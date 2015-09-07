<?hh
namespace Elastica;

use Elastica\Cluster\Health;
use Elastica\Cluster\Settings;
use Elastica\Exception\NotImplementedException;
use Elastica\Node;
use Indexish;

/**
 * Cluster informations for elasticsearch.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster.html
 */
class Cluster
{
    /**
     * Client.
     *
     * @var \Elastica\Client Client object
     */
    protected Client $_client;

    /**
     * Cluster state response.
     *
     * @var \Elastica\Response
     */
    protected Response $_response;

    /**
     * Cluster state data.
     *
     * @var array
     */
    protected Indexish<string, mixed> $_data;

    static public async function create(Client $client) : Awaitable<Cluster>
    {
        $response = await self::_refreshRequest($client);
        return new self($client, $response);
    }

    /**
     * Creates a cluster object.
     *
     * @param \Elastica\Client $client Connection client object
     */
    protected function __construct(Client $client, Response $response)
    {
        $this->_client = $client;
        $this->onResponse($response);
    }

    protected static function _refreshRequest(Client $client) : Awaitable<Response>
    {
        $path = '_cluster/state';
        return $client->request($path, Request::GET);
    }

    private function onResponse(Response $response) : void
    {
        $this->_response = $response;
        $data = $response->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        $this->_data = $data;
    }

    /**
     * Refreshes all cluster information (state).
     */
    public async function refresh() : Awaitable<Response>
    {
        $response = await self::_refreshRequest($this->getClient());
        $this->onResponse($response);
        return $response;
    }

    /**
     * Returns the response object.
     *
     * @return \Elastica\Response Response object
     */
    public function getResponse() : Response
    {
        return $this->_response;
    }

    /**
     * Return list of index names.
     *
     * @return array List of index names
     */
    public function getIndexNames() : array<string>
    {
        $metaData = /* UNSAFE_EXPR */ $this->_data['metadata']['indices'];

        $indices = array();
        foreach ($metaData as $key => $value) {
            $indices[] = $key;
        }

        return $indices;
    }

    /**
     * Returns the full state of the cluster.
     *
     * @return array State array
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-state.html
     */
    public function getState() : Indexish<string, mixed>
    {
        return $this->_data;
    }

    /**
     * Returns a list of existing node names.
     *
     * @return array List of node names
     */
    public function getNodeNames() : array<string>
    {
        $data = $this->getState();
        $nodeNames = array();
        foreach (/* UNSAFE_EXPR */ $data['nodes'] as $node) {
            $nodeNames[] = $node['name'];
        }

        return $nodeNames;
    }

    /**
     * Returns all nodes of the cluster.
     *
     * @return \Elastica\Node[]
     */
    public function getNodes() : array<Node>
    {
        $nodes = array();
        $data = $this->getState();
        if (isset($data['nodes'])) {
               foreach (/* UNSAFE_EXPR */ $data['nodes'] as $id => $name) {
                   $nodes[] = new Node($id, $this->getClient());
               }
        }

        return $nodes;
    }

    /**
     * Returns the client object.
     *
     * @return \Elastica\Client Client object
     */
    public function getClient() : Client
    {
        return $this->_client;
    }

    /**
     * Returns the cluster information (not implemented yet).
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-info.html
     *
     * @param array $args Additional arguments
     *
     * @throws \Elastica\Exception\NotImplementedException
     */
    public function getInfo(array $args) : void
    {
        throw new NotImplementedException('not implemented yet');
    }

    /**
     * Return Cluster health.
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-health.html
     *
     * @return Awaitable<\Elastica\Cluster\Health>
     */
    public function getHealth() : Awaitable<Health>
    {
        return Health::create($this->getClient());
    }

    /**
     * Return Cluster settings.
     *
     * @return \Elastica\Cluster\Settings
     */
    public function getSettings() : Settings
    {
        return new Settings($this->getClient());
    }

    /**
     * Shuts down the complete cluster.
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-shutdown.html
     *
     * @param string $delay OPTIONAL Seconds to shutdown cluster after (default = 1s)
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function shutdown(string $delay = '1s') : Awaitable<Response>
    {
        $path = '_shutdown?delay='.$delay;

        return $this->_client->request($path, Request::POST);
    }
}
