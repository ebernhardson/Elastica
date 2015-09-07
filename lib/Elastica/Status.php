<?hh
namespace Elastica;

use Elastica\Exception\ResponseException;
use Elastica\Index\Status as IndexStatus;
use Indexish;

/**
 * Elastica general status.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-status.html
 */
class Status
{
    /**
     * Contains all status infos.
     *
     * @var \Elastica\Response Response object
     */
    protected Response $_response;

    /**
     * Data.
     *
     * @var array Data
     */
    protected Indexish<string, mixed> $_data = array();

    /**
     * Client object.
     *
     * @var \Elastica\Client Client object
     */
    protected Client $_client;

    /**
     * Constructs Status object.
     *
     * @param \Elastica\Client $client Client object
     *
     * @return Awaitable<\Elastica\Status>
     */
    public static async function create(Client $client) : Awaitable<Status>
    {
        $response = await self::_refreshRequest($client);
        return new self($client, $response);
    }

    /**
     * Constructs Status object.
     *
     * @param \Elastica\Client $client Client object
     */
    protected function __construct(Client $client, Response $response)
    {
        $this->_client = $client;
        $this->onResponse($response);
    }

    /**
     * Returns status data.
     *
     * @return array Status data
     */
    public function getData() : Indexish<string, mixed>
    {
        return $this->_data;
    }

    /**
     * Returns status objects of all indices.
     *
     * @return Awaitable<array|\Elastica\Index\Status[]> List of Elastica\Client\Index objects
     */
    public async function getIndexStatuses() : Awaitable<array>
    {
        $statuses = array();
        foreach ($this->getIndexNames() as $name) {
            $index = new Index($this->_client, $name);
            $statuses[] = await IndexStatus::create($index);
        }

        return $statuses;
    }

    /**
     * Returns a list of the existing index names.
     *
     * @return array Index names list
     */
    public function getIndexNames() : array
    {
        return /* UNSAFE_EXPR */ array_keys($this->_data['indices']);
    }

    /**
     * Checks if the given index exists.
     *
     * @param string $name Index name to check
     *
     * @return bool True if index exists
     */
    public function indexExists(string $name) : bool
    {
        return in_array($name, $this->getIndexNames());
    }

    /**
     * Checks if the given alias exists.
     *
     * @param string $name Alias name
     *
     * @return Awaitable<bool> True if alias exists
     */
    public async function aliasExists(string $name) : Awaitable<bool>
    {
        $indices = await $this->getIndicesWithAlias($name);
        return count($indices) > 0;
    }

    /**
     * Returns an array with all indices that the given alias name points to.
     *
     * @param string $alias Alias name
     *
     * @return Awaitable<array|\Elastica\Index[]> List of Elastica\Index
     */
    public async function getIndicesWithAlias(string $alias) : Awaitable<array>
    {
        $response = null;
        try {
            $response = await $this->_client->request('/_alias/'.$alias);
        } catch (ResponseException $e) {
            $transferInfo = $e->getResponse()->getTransferInfo();
            // 404 means the index alias doesn't exist which means no indexes have it.
            if ($transferInfo['http_code'] === 404) {
                return array();
            }
            // If we don't have a 404 then this is still unexpected so rethrow the exception.
            throw $e;
        }
        $indices = array();
        foreach (/* UNSAFE_EXPR */ $response->getData() as $name => $unused) {
            $indices[] = new Index($this->_client, $name);
        }

        return $indices;
    }

    /**
     * Returns response object.
     *
     * @return \Elastica\Response Response object
     */
    public function getResponse() : Response
    {
        return $this->_response;
    }

    /**
     * Return shards info.
     *
     * @return array Shards info
     */
    public function getShards() : array
    {
        return /* UNSAFE_EXPR */ $this->_data['shards'];
    }

    /**
     * Refresh status object.
     *
     * @return Awaitable<void>
     */
    public async function refresh() : Awaitable<void>
    {
        $response = await self::_refreshRequest($this->_client);
        $this->onResponse($response);
    }

    public static function _refreshRequest(Client $client) : Awaitable<Response>
    {
        $path = '_status';
        return $client->request($path, Request::GET);
    }

    private function onResponse(Response $response) : void
    {
        $this->_response = $response;
        $data = $this->getResponse()->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        $this->_data = $data;
    }

    /**
     * Refresh serverStatus object.
     *
     * @return Awaitable<mixed>
     */
    public async function getServerStatus() : Awaitable<mixed>
    {
        $path = '';
        $response = await $this->_client->request($path, Request::GET);

        return $response->getData();
    }
}
