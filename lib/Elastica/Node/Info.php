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
class Info
{
    protected $_id = null;

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
     * Query parameters.
     *
     * @var array
     */
    protected array $_params = array();

    /**
     * Create new info object for node.
     *
     * @param \Elastica\Node $node   Node object
     * @param array          $params List of params to return. Can be: settings, os, process, jvm, thread_pool, network, transport, http
     */
    static public async function create(BaseNode $node, array $params = array()) : Awaitable<Info>
    {
        $response = await self::_refreshRequest($node, $params);
        return new self($node, $params, $response);
    }

    /**
     * Create new info object for node.
     *
     * @param \Elastica\Node $node   Node object
     */
    protected function __construct(BaseNode $node, array $params, Response $response)
    {
        $this->_node = $node;
        $this->onResponse($response, $params);
    }

    /**
     * Returns the entry in the data array based on the params.
     * Several params possible.
     *
     * Example 1: get('os', 'mem', 'total') returns total memory of the system the
     * node is running on
     * Example 2: get('os', 'mem') returns an array with all mem infos
     *
     * @return mixed Data array entry or null if not found
     */
    public function get(...) : mixed
    {
        $data = $this->getData();

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
     * Return port of the node.
     *
     * @return string Returns Node port
     */
    public function getPort() : string
    {
        // Returns string in format: inet[/192.168.1.115:9201]
        $data = $this->get('http_address');
        $data = substr($data, 6, strlen($data) - 7);
        $data = explode(':', $data);

        return $data[1];
    }

    /**
     * Return IP of the node.
     *
     * @return string Returns Node ip address
     */
    public function getIp() : string
    {
        // Returns string in format: inet[/192.168.1.115:9201]
        $data = $this->get('http_address');
        $data = substr($data, 6, strlen($data) - 7);
        $data = explode(':', $data);

        return $data[0];
    }

    /**
     * Return data regarding plugins installed on this node.
     *
     * @return Awaitable<array> plugin data
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-info.html
     */
    public async function getPlugins() : Awaitable<array>
    {
        if (!in_array('plugins', $this->_params)) {
            //Plugin data was not retrieved when refresh() was called last. Get it now.
            $this->_params[] = 'plugins';
            await $this->refresh($this->_params);
        }

        return (array) $this->get('plugins');
    }

    /**
     * Check if the given plugin is installed on this node.
     *
     * @param string $name plugin name
     *
     * @return Awaitable<bool> true if the plugin is installed, false otherwise
     */
    public async function hasPlugin(@string $name) : Awaitable<bool>
    {
        $plugins = await $this->getPlugins();
        foreach ($plugins as $plugin) {
            if ($plugin['name'] == $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return all info data.
     *
     * @return array Data array
     */
    public function getData() : array
    {
        return $this->_data;
    }

    /**
     * Return node object.
     *
     * @return \Elastica\Node Node object
     */
    public function getNode() : BaseNode
    {
        return $this->_node;
    }

    /**
     * @return string Unique node id
     */
    public function getId() : string
    {
        return $this->_id;
    }

    /**
     * @return string Node name
     */
    public function getName() : string
    {
        return $this->_data['name'];
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
     * @param array $params Params to return (default none). Possible options: settings, os, process, jvm, thread_pool, network, transport, http, plugin
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    private static function _refreshRequest(BaseNode $node, array $params = array()) : Awaitable<Response>
    {
        $path = '_nodes/'.$node->getId();

        if (!empty($params)) {
            $path .= '?';
            foreach ($params as $param) {
                $path .= $param.'=true&';
            }
        }

        return $node->getClient()->request($path, Request::GET);
    }

    public async function refresh(array $params = array()) : Awaitable<void>
    {
        $response = await self::_refreshRequest($this->getNode(), $params);
        $this->onResponse($response, $params);
    }

    private function onResponse(Response $response, array $params) : void
    {
        $this->_response = $response;
        $this->_params = $params;

        $data = $this->getResponse()->getData();

        if (!$data instanceof Indexish) {
            throw new \RuntimeException('expected array');
        }
        $this->_data = reset($data['nodes']);
        $this->_id = key($data['nodes']);
        $this->getNode()->setId($this->getId());
    }
}
