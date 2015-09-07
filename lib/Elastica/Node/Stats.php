<?hh
namespace Elastica\Node;

use Elastica\Node as BaseNode;
use Elastica\Request;
use Elastica\Response;
use Indexish;

/**
 * Elastica cluster node object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-status.html
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
     * Stats data.
     *
     * @var array stats data
     */
    protected array $_data = array();

    /**
     * Node.
     *
     * @var \Elastica\Node Node object
     */
    protected BaseNode $_node;

    /**
     * Create new stats for node.
     *
     * @param \Elastica\Node $node Elastica node object
     *
     * @return Awaitable<\Elastica\Node\Stats>
     */
    static public async function create(BaseNode $node) : Awaitable<Stats>
    {
        $response = await self::_refreshRequest($node);
        return new self($node, $response);
    }

    /**
     * Create new stats for node.
     *
     * @param \Elastica\Node $node Elastica node object
     */
    protected function __construct(BaseNode $node, Response $response)
    {
        $this->_node = $node;
        $this->onResponse($response);
    }

    /**
     * Returns all node stats as array based on the arguments.
     *
     * Several arguments can be use
     * get('index', 'test', 'example')
     *
     * @return ?array Node stats for the given field or null if not found
     */
    public function get() : ?array
    {
        $data = $this->getData();

        foreach (func_get_args() as $arg) {
            if (isset($data[$arg])) {
                $data = $data[$arg];
            } else {
                return null;
            }
        }

        return $data;
    }

    /**
     * Returns all stats data.
     *
     * @return array Data array
     */
    public function getData() : array
    {
        return $this->_data;
    }

    /**
     * Returns node object.
     *
     * @return \Elastica\Node Node object
     */
    public function getNode() : BaseNode
    {
        return $this->_node;
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
     * Reloads all nodes information. Has to be called if informations changed.
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public async function refresh() : Awaitable<Response>
    {
        $response = await self::_refreshRequest($this->getNode());
        return $this->onResponse($response);
    }

    protected static async function _refreshRequest(BaseNode $node) : Awaitable<Response>
    {
        $name = await $node->getName();
        $path = '_nodes/'.$name.'/stats';
        return await $node->getClient()->request($path, Request::GET);
    }

    private function onResponse(Response $response) : Response
    {
        $this->_response = $response;
        $data = $this->getResponse()->getData();
        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        $this->_data = reset($data['nodes']);
        return $this->getResponse();
    }
}
