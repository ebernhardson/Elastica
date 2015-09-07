<?hh
namespace Elastica;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Elastica Request object.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Request extends Param
{
    const HEAD = 'HEAD';
    const POST = 'POST';
    const PUT = 'PUT';
    const GET = 'GET';
    const DELETE = 'DELETE';

    /**
     * @var \Elastica\Connection
     */
    protected $_connection;

    /**
     * Construct.
     *
     * @param string     $path       Request path
     * @param string     $method     OPTIONAL Request method (use const's) (default = self::GET)
     * @param array      $data       OPTIONAL Data array
     * @param array      $query      OPTIONAL Query params
     * @param Connection $connection
     *
     * @return \Elastica\Request OPTIONAL Connection object
     */
    public function __construct(string $path, string $method = self::GET, mixed $data = array(), Indexish<string, mixed> $query = array(), ?Connection $connection = null)
    {
        $this->setPath($path);
        $this->setMethod($method);
        $this->setData($data);
        $this->setQuery($query);

        if ($connection) {
            $this->setConnection($connection);
        }
    }

    /**
     * Sets the request method. Use one of the for consts.
     *
     * @param string $method Request method
     *
     * @return $this
     */
    public function setMethod(string $method) : this
    {
        return $this->setParam('method', $method);
    }

    /**
     * Get request method.
     *
     * @return string Request method
     */
    public function getMethod() : string
    {
        return (string)$this->getParam('method');
    }

    /**
     * Sets the request data.
     *
     * @param array $data Request data
     *
     * @return $this
     */
    public function setData(mixed $data) : this
    {
        return $this->setParam('data', $data);
    }

    /**
     * Return request data.
     *
     * @return string|array Request data
     */
    public function getData() : mixed
    {
        return $this->getParam('data');
    }

    /**
     * Sets the request path.
     *
     * @param string $path Request path
     *
     * @return $this
     */
    public function setPath(string $path) : this
    {
        return $this->setParam('path', $path);
    }

    /**
     * Return request path.
     *
     * @return string Request path
     */
    public function getPath() : string
    {
        return (string)$this->getParam('path');
    }

    /**
     * Return query params.
     *
     * @return array Query params
     */
    public function getQuery() : array
    {
        return (array)$this->getParam('query');
    }

    /**
     * @param array $query
     *
     * @return $this
     */
    public function setQuery(Indexish<string, mixed> $query = array()) : this
    {
        return $this->setParam('query', $query);
    }

    /**
     * @param \Elastica\Connection $connection
     *
     * @return $this
     */
    public function setConnection(Connection $connection) : this
    {
        $this->_connection = $connection;

        return $this;
    }

    /**
     * Return Connection Object.
     *
     * @throws Exception\InvalidException If no valid connection was setted
     *
     * @return \Elastica\Connection
     */
    public function getConnection() : Connection
    {
        if (empty($this->_connection)) {
            throw new InvalidException('No valid connection object set');
        }

        return $this->_connection;
    }

    /**
     * Sends request to server.
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function send() : Awaitable<Response>
    {
        $transport = $this->getConnection()->getTransportObject();

        // Refactor: Not full toArray needed in exec?
        return $transport->exec($this, $this->getConnection()->toArray());
    }

    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $data = $this->getParams();
        if ($this->_connection) {
            $data['connection'] = $this->_connection->getParams();
        }

        return $data;
    }

    /**
     * Converts request to curl request format.
     *
     * @return string
     */
    public function toString() : string
    {
        return JSON::stringify($this->toArray());
    }

    /**
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }
}
