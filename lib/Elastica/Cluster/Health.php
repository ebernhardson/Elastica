<?hh // strict
namespace Elastica\Cluster;

use Elastica\Client;
use Elastica\Cluster\Health\Index;
use Elastica\Request;
use Indexish;

/**
 * Elastic cluster health.
 *
 * @author Ray Ward <ray.ward@bigcommerce.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-health.html
 */
class Health
{
    /**
     * @var \Elastica\Client Client object.
     */
    protected Client $_client;

    /**
     * @var array The cluster health data.
     */
    protected Indexish<string, mixed> $_data;

    /**
     * @param \Elastica\Client $client The Elastica client.
     * @return Awaitable<Health>
     */
    static public async function create(Client $client) : Awaitable<Health> {
        $response = await self::_retrieveHealthData($client);
        return new self($client, $response);
    }

    /**
     * @param \Elastica\Client $client The Elastica client.
     */
    public function __construct(Client $client, Indexish<string, mixed> $data)
    {
        $this->_client = $client;
        $this->_data = $data;
    }

    /**
     * Retrieves the health data from the cluster.
     *
     * @return Awaitable<array>
     */
    protected static async function _retrieveHealthData(Client $client) : Awaitable<Indexish<string, mixed>>
    {
        $path = '_cluster/health?level=shards';
        $response = await $client->request($path, Request::GET);

        $data = $response->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        return $data;
    }

    /**
     * Gets the health data.
     *
     * @return array
     */
    public function getData() : Indexish<string, mixed>
    {
        return $this->_data;
    }

    /**
     * Refreshes the health data for the cluster.
     *
     * @return Awaitable<$this>
     */
    public async function refresh() : Awaitable<Health>
    {
        $this->_data = await self::_retrieveHealthData($this->_client);

        return $this;
    }

    /**
     * Gets the name of the cluster.
     *
     * @return string
     */
    public function getClusterName() : string
    {
        return (string) $this->_data['cluster_name'];
    }

    /**
     * Gets the status of the cluster.
     *
     * @return string green, yellow or red.
     */
    public function getStatus() : string
    {
        return (string) $this->_data['status'];
    }

    /**
     * TODO determine the purpose of this.
     *
     * @return bool
     */
    public function getTimedOut() : bool
    {
        return (bool) $this->_data['timed_out'];
    }

    /**
     * Gets the number of nodes in the cluster.
     *
     * @return int
     */
    public function getNumberOfNodes() : int
    {
        return (int) $this->_data['number_of_nodes'];
    }

    /**
     * Gets the number of data nodes in the cluster.
     *
     * @return int
     */
    public function getNumberOfDataNodes() : int
    {
        return (int) $this->_data['number_of_data_nodes'];
    }

    /**
     * Gets the number of active primary shards.
     *
     * @return int
     */
    public function getActivePrimaryShards() : int
    {
        return (int) $this->_data['active_primary_shards'];
    }

    /**
     * Gets the number of active shards.
     *
     * @return int
     */
    public function getActiveShards() : int
    {
        return (int) $this->_data['active_shards'];
    }

    /**
     * Gets the number of relocating shards.
     *
     * @return int
     */
    public function getRelocatingShards() : int
    {
        return (int) $this->_data['relocating_shards'];
    }

    /**
     * Gets the number of initializing shards.
     *
     * @return int
     */
    public function getInitializingShards() : int
    {
        return (int) $this->_data['initializing_shards'];
    }

    /**
     * Gets the number of unassigned shards.
     *
     * @return int
     */
    public function getUnassignedShards() : int
    {
        return (int) $this->_data['unassigned_shards'];
    }

    /**
     * Gets the status of the indices.
     *
     * @return \Elastica\Cluster\Health\Index[]
     */
    public function getIndices() : Vector<Index>
    {
        $indices = Vector {};
        foreach (/* UNSAFE_EXPR */ $this->_data['indices'] as $indexName => $index) {
            $indices[] = new Index($indexName, $index);
        }

        return $indices;
    }
}
