<?hh
namespace Elastica\Index;

use Elastica\Index as BaseIndex;
use Elastica\Request;
use Elastica\Response;
use Indexish;

/**
 * Elastica index stats object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-stats.html
 */
class Stats
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
     * Construct.
     *
     * @param \Elastica\Index $index Index object
     *
     * @return Awaitable<\Elastica\Index\Stats>
     */
    static public async function create(BaseIndex $index) : Awaitable<Stats>
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
     * Returns the raw stats info.
     *
     * @return array Stats info
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
    public function get(...) : mixed
    {
        $data = $this->getData();

        foreach (func_get_args() as $arg) {
            if (isset(/* UNSAFE_EXPR */ $data[$arg])) {
                $data = /* UNSAFE_EXPR */ $data[$arg];
            } else {
                return;
            }
        }

        return $data;
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
        $path = '_stats';
        return $index->request($path, Request::GET);
    }

    /**
     * Reloads all status data of this object.
     *
     * @return Awaitable<null>
     */
    public async function refresh() : Awaitable<Response>
    {
        $response = await self::_refreshRequest($this->getIndex());
        $this->onResponse($response);
        return $response;
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
