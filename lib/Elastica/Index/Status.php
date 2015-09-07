<?hh
namespace Elastica\Index;

use Elastica\Index as BaseIndex;
use Elastica\Request;
use Elastica\Response;
use Indexish;

/**
 * Elastica index status object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-status.html
 */
class Status
{
    /**
     * Response.
     *
     * @var \Elastica\Response Response object
     */
    protected Response $_response;

    /**
     * Stats info.
     *
     * @var array Stats info
     */
    protected Indexish<string, mixed> $_data;

    /**
     * Index.
     *
     * @var \Elastica\Index Index object
     */
    protected BaseIndex $_index;

    /**
     * Create.
     *
     * @param \Elastica\Index $index Index object
     *
     * @return Awaitable<\Elastica\Status>
     */
    static public async function create(BaseIndex $index) : Awaitable<Status>
    {
        $response = await self::_refreshRequest($index);
        return new self($index, $response);
    }

    /**
     * Construct.
     *
     * @param \Elastica\Index $index Index object
     */
    protected function __construct(BaseIndex $index, Response $response)
    {
        $this->_index = $index;
        $this->onResponse($response);
    }

    /**
     * Returns all status info.
     *
     * @return array Status info
     */
    public function getData() : Indexish<string, mixed>
    {
        return $this->_data;
    }

    /**
     * Returns the entry in the data array based on the params.
     * Various params possible.
     *
     * @return mixed Data array entry or null if not found
     */
    public function get() : mixed
    {
        $data = $this->getData();
        $data = /* UNSAFE_EXPR */ $data['indices'][$this->getIndex()->getName()];

        foreach (func_get_args() as $arg) {
            if (isset($data[$arg])) {
                $data = $data[$arg];
            } else {
                return;
            }
        }

        return $data;
    }

    /**
     * Returns all index aliases.
     *
     * @return Awaitable<array> Aliases
     */
    public async function getAliases() : Awaitable<array>
    {
        $response = await $this->getIndex()->request('_aliases', \Elastica\Request::GET);
        $responseData = $response->getData();

        $data = /* UNSAFE_EXPR */ $responseData[$this->getIndex()->getName()];
        if (!empty($data['aliases'])) {
            return array_keys($data['aliases']);
        }

        return array();
    }

    /**
     * Returns all index settings.
     *
     * @return Awaitable<array> Index settings
     */
    public async function getSettings() : Awaitable<array>
    {
        $response = await $this->getIndex()->request('_settings', \Elastica\Request::GET);
        $responseData = $response->getData();

        return /* UNSAFE_EXPR */ $responseData[$this->getIndex()->getName()]['settings'];
    }

    /**
     * Checks if the index has the given alias.
     *
     * @param string $name Alias name
     *
     * @return Awaitable<bool>
     */
    public async function hasAlias(string $name) : Awaitable<bool>
    {
        $aliases = await $this->getAliases();
        return in_array($name, $aliases);
    }

    /**
     * Returns the index object.
     *
     * @return \Elastica\Index Index object
     */
    public function getIndex() : BaseIndex
    {
        return $this->_index;
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

    protected static function _refreshRequest(BaseIndex $index) : Awaitable<Response>
    {
        $path = '_status';
        return $index->request($path, Request::GET);
    }

    /**
     * Reloads all status data of this object.
     *
     * @return Awaitable<void>
     */
    public async function refresh() : Awaitable<void>
    {
        $response = await self::_refreshRequest($this->getIndex());
        $this->onResponse($response);
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
}
