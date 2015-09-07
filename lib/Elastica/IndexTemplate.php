<?hh
namespace Elastica;

use Elastica\Exception\InvalidException;

/**
 * Elastica index template object.
 *
 * @author Dmitry Balabka <dmitry.balabka@gmail.com>
 * 
 * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html
 */
class IndexTemplate
{
    /**
     * Index template name.
     *
     * @var string Index pattern
     */
    protected string $_name;

    /**
     * Client object.
     *
     * @var \Elastica\Client Client object
     */
    protected Client $_client;

    /**
     * Creates a new index template object.
     *
     * @param \Elastica\Client $client Client object
     * @param string           $name   Index template name
     *
     * @throws \Elastica\Exception\InvalidException
     */
    public function __construct(Client $client, string $name)
    {
        $this->_client = $client;
        $this->_name = $name;
    }

    /**
     * Deletes the index template.
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function delete() : Awaitable<Response>
    {
        $response = $this->request(Request::DELETE);

        return $response;
    }

    /**
     * Creates a new index template with the given arguments.
     *
     * @link https://www.elastic.co/guide/en/elasticsearch/reference/current/indices-templates.html
     *
     * @param array $args OPTIONAL Arguments to use
     *
     * @return Awaitable<\Elastica\Response>
     */
    public function create(array $args = array()) : Awaitable<Response>
    {
        return $this->request(Request::PUT, $args);
    }

    /**
     * Checks if the given index template is already created.
     *
     * @return Awaitable<bool> True if index exists
     */
    public async function exists() : Awaitable<bool>
    {
        $response = await $this->request(Request::HEAD);
        $info = $response->getTransferInfo();

        return (bool) ($info['http_code'] == 200);
    }

    /**
     * Returns the index template name.
     *
     * @return string Index name
     */
    public function getName() : string
    {
        return $this->_name;
    }

    /**
     * Returns index template client.
     *
     * @return \Elastica\Client Index client object
     */
    public function getClient() : Client
    {
        return $this->_client;
    }

    /**
     * Makes calls to the elasticsearch server based on this index template name.
     *
     * @param string $method Rest method to use (GET, POST, DELETE, PUT)
     * @param array  $data   OPTIONAL Arguments as array
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function request(string $method, array $data = array()) : Awaitable<Response>
    {
        $path = '/_template/'.$this->getName();

        return $this->getClient()->request($path, $method, $data);
    }
}
