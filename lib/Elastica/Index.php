<?hh
namespace Elastica;

use Elastica\Exception\InvalidException;
use Elastica\Exception\ResponseException;
use Elastica\Index\Settings as IndexSettings;
use Elastica\Index\Stats as IndexStats;
use Elastica\Index\Status as IndexStatus;
use Indexish;

/**
 * Elastica index object.
 *
 * Handles reads, deletes and configurations of an index
 *
 * @author   Nicolas Ruflin <spam@ruflin.com>
 */
class Index implements SearchableInterface
{
    /**
     * Index name.
     *
     * @var string Index name
     */
    protected string $_name = '';

    /**
     * Client object.
     *
     * @var \Elastica\Client Client object
     */
    protected Client $_client;

    /**
     * Creates a new index object.
     *
     * All the communication to and from an index goes of this object
     *
     * @param \Elastica\Client $client Client object
     * @param string           $name   Index name
     *
     * @throws \Elastica\Exception\InvalidException
     */
    public function __construct(Client $client, mixed $name)
    {
        $this->_client = $client;

        if (!is_scalar($name)) {
            throw new InvalidException('Index name should be a scalar type');
        }
        $this->_name = (string) $name;
    }

    /**
     * Returns a type object for the current index with the given name.
     *
     * @param string $type Type name
     *
     * @return \Elastica\Type Type object
     */
    public function getType(@string $type) : Type
    {
        return new Type($this, $type);
    }

    /**
     * Returns the current status of the index.
     *
     * @return Awaitable<\Elastica\Index\Status> Index status
     */
    public function getStatus() : Awaitable<IndexStatus>
    {
        return IndexStatus::create($this);
    }

    /**
     * Return Index Stats.
     *
     * @return Awaitable<\Elastica\Index\Stats>
     */
    public function getStats() : Awaitable<IndexStats>
    {
        return IndexStats::create($this);
    }

    /**
     * Gets all the type mappings for an index.
     *
     * @return Awaitable<array>
     */
    public async function getMapping() : Awaitable<array>
    {
        $path = '_mapping';

        $response = await $this->request($path, Request::GET);
        $data = $response->getData();

        // Get first entry as if index is an Alias, the name of the mapping is the real name and not alias name
        $mapping = array_shift($data);

        if (isset($mapping['mappings'])) {
            return $mapping['mappings'];
        }

        return array();
    }

    /**
     * Returns the index settings object.
     *
     * @return \Elastica\Index\Settings Settings object
     */
    public function getSettings() : IndexSettings
    {
        return new IndexSettings($this);
    }

    /**
     * Uses _bulk to send documents to the server.
     *
     * @param array|\Elastica\Document[] $docs Array of Elastica\Document
     *
     * @return Awaitable<\Elastica\Bulk\ResponseSet>
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     */
    public function updateDocuments(array $docs) : Awaitable<Bulk\ResponseSet>
    {
        foreach ($docs as $doc) {
            $doc->setIndex($this->getName());
        }

        return $this->getClient()->updateDocuments($docs);
    }

    /**
     * Uses _bulk to send documents to the server.
     *
     * @param array|\Elastica\Document[] $docs Array of Elastica\Document
     *
     * @return Awaitable<\Elastica\Bulk\ResponseSet>
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     */
    public function addDocuments(array $docs) : Awaitable<Bulk\ResponseSet>
    {
        foreach ($docs as $doc) {
            $doc->setIndex($this->getName());
        }

        return $this->getClient()->addDocuments($docs);
    }

    /**
     * Deletes entries in the db based on a query.
     *
     * @param \Elastica\Query|string $query   Query object
     * @param array                  $options Optional params
     *
     * @return Awaitable<\Elastica\Response>
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-delete-by-query.html
     */
    public function deleteByQuery(mixed $query, Indexish<string, mixed> $options = array()) : Awaitable<Response>
    {
        if (is_string($query)) {
            // query_string queries are not supported for delete by query operations
            $options['q'] = $query;

            return $this->request('_query', Request::DELETE, array(), $options);
        }
        $query = Query::create($query);

        return $this->request('_query', Request::DELETE, array('query' => $query->getQuery()->toArray()), $options);
    }

    /**
     * Deletes the index.
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function delete() : Awaitable<Response>
    {
        $response = $this->request('', Request::DELETE);

        return $response;
    }

    /**
     * Uses _bulk to delete documents from the server.
     *
     * @param array|\Elastica\Document[] $docs Array of Elastica\Document
     *
     * @return Awaitable<\Elastica\Bulk\ResponseSet>
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     */
    public function deleteDocuments(array $docs) : Awaitable<Bulk\ResponseSet>
    {
        foreach ($docs as $doc) {
            $doc->setIndex($this->getName());
        }

        return $this->getClient()->deleteDocuments($docs);
    }

    /**
     * Optimizes search index.
     *
     * Detailed arguments can be found here in the link
     *
     * @param array $args OPTIONAL Additional arguments
     *
     * @return Awaitable<array> Server response
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-optimize.html
     */
    public function optimize(array $args = array()) : Awaitable<Response>
    {
        return $this->request('_optimize', Request::POST, array(), $args);
    }

    /**
     * Refreshes the index.
     *
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-refresh.html
     */
    public function refresh() : Awaitable<Response>
    {
        return $this->request('_refresh', Request::POST, array());
    }

    /**
     * Creates a new index with the given arguments.
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-create-index.html
     *
     * @param array      $args    OPTIONAL Arguments to use
     * @param bool|array $options OPTIONAL
     *                            bool=> Deletes index first if already exists (default = false).
     *                            array => Associative array of options (option=>value)
     *
     * @throws \Elastica\Exception\InvalidException
     * @throws \Elastica\Exception\ResponseException
     *
     * @return Awaitable<array> Server response
     */
    public async function create(array $args = array(), mixed $options = null) : Awaitable<Response>
    {
        $path = '';
        $query = array();

        if (is_bool($options)) {
            if ($options) {
                try {
                    await $this->delete();
                } catch (ResponseException $e) {
                    // Table can't be deleted, because doesn't exist
                }
            }
        } else {
            if ($options instanceof Indexish) {
                foreach ($options as $key => $value) {
                    switch ($key) {
                        case 'recreate' :
                            try {
                                await $this->delete();
                            } catch (ResponseException $e) {
                                // Table can't be deleted, because doesn't exist
                            }
                            break;
                        case 'routing' :
                            $query = array('routing' => $value);
                            break;
                        default:
                            throw new InvalidException('Invalid option '.$key);
                            break;
                    }
                }
            }
        }

        return await $this->request($path, Request::PUT, $args, $query);
    }

    /**
     * Checks if the given index is already created.
     *
     * @return Awaitable<bool> True if index exists
     */
    public async function exists() : Awaitable<bool>
    {
        $response = await $this->getClient()->request($this->getName(), Request::HEAD);
        $info = $response->getTransferInfo();

        return (bool) ($info['http_code'] == 200);
    }

    /**
     * @param string|array|\Elastica\Query $query
     * @param int|array                    $options
     *
     * @return \Elastica\Search
     */
    public function createSearch(mixed $query = '', mixed $options = null) : Search
    {
        $search = new Search($this->getClient());
        $search->addIndex($this);
        $search->setOptionsAndQuery($options, $query);

        return $search;
    }

    /**
     * Searches in this index.
     *
     * @param string|array|\Elastica\Query $query   Array with all query data inside or a Elastica\Query object
     * @param int|array                    $options OPTIONAL Limit or associative array of options (option=>value)
     *
     * @return Awaitable<\Elastica\ResultSet> ResultSet with all results inside
     *
     * @see \Elastica\SearchableInterface::search
     */
    public function search(mixed $query = '', mixed $options = null) : Awaitable<ResultSet>
    {
        $search = $this->createSearch($query, $options);

        return $search->search();
    }

    /**
     * Counts results of query.
     *
     * @param string|array|\Elastica\Query $query Array with all query data inside or a Elastica\Query object
     *
     * @return Awaitable<int> number of documents matching the query
     *
     * @see \Elastica\SearchableInterface::count
     */
    public async function count(mixed $query = '') : Awaitable<int>
    {
        $search = $this->createSearch($query);

        $res = await $search->count();

        return (int) $res;
    }

    /**
     * Opens an index.
     *
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-open-close.html
     */
    public function open() : Awaitable<Response>
    {
        return $this->request('_open', Request::POST);
    }

    /**
     * Closes the index.
     *
       @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-open-close.html
     */
    public function close() : Awaitable<Response>
    {
        return $this->request('_close', Request::POST);
    }

    /**
     * Returns the index name.
     *
     * @return string Index name
     */
    public function getName() : string
    {
        return $this->_name;
    }

    /**
     * Returns index client.
     *
     * @return \Elastica\Client Index client object
     */
    public function getClient() : Client
    {
        return $this->_client;
    }

    /**
     * Adds an alias to the current index.
     *
     * @param string $name    Alias name
     * @param bool   $replace OPTIONAL If set, an existing alias will be replaced
     *
     * @return Awaitable<\Elastica\Response> Response
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-aliases.html
     */
    public async function addAlias(string $name, bool $replace = false) : Awaitable<Response>
    {
        $path = '_aliases';

        $data = array('actions' => array());

        if ($replace) {
            $status = await Status::create($this->getClient());
            $indices = await $status->getIndicesWithAlias($name);
            foreach ($indices as $index) {
                $data['actions'][] = array('remove' => array('index' => $index->getName(), 'alias' => $name));
            }
        }

        $data['actions'][] = array('add' => array('index' => $this->getName(), 'alias' => $name));

        return await $this->getClient()->request($path, Request::POST, $data);
    }

    /**
     * Removes an alias pointing to the current index.
     *
     * @param string $name Alias name
     *
     * @return Awaitable<\Elastica\Response> Response
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-aliases.html
     */
    public function removeAlias(string $name) : Awaitable<Response>
    {
        $path = '_aliases';

        $data = array('actions' => array(array('remove' => array('index' => $this->getName(), 'alias' => $name))));

        return $this->getClient()->request($path, Request::POST, $data);
    }

    /**
     * Clears the cache of an index.
     *
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-clearcache.html
     */
    public function clearCache() : Awaitable<Response>
    {
        $path = '_cache/clear';
        // TODO: add additional cache clean arguments
        return $this->request($path, Request::POST);
    }

    /**
     * Flushes the index to storage.
     *
     * @param bool $refresh
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-flush.html
     */
    public function flush(bool $refresh = false) : Awaitable<Response>
    {
        $path = '_flush';

        return $this->request($path, Request::POST, array(), array('refresh' => $refresh));
    }

    /**
     * Can be used to change settings during runtime. One example is to use it for bulk updating.
     *
     * @param array $data Data array
     *
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-update-settings.html
     */
    public function setSettings(array $data) : Awaitable<Response>
    {
        return $this->request('_settings', Request::PUT, $data);
    }

    /**
     * Makes calls to the elasticsearch server based on this index.
     *
     * @param string $path   Path to call
     * @param string $method Rest method to use (GET, POST, DELETE, PUT)
     * @param array  $data   OPTIONAL Arguments as array
     * @param array  $query  OPTIONAL Query params
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public function request(string $path, string $method, mixed $data = array(), Indexish<string, mixed> $query = array()) : Awaitable<Response>
    {
        $path = $this->getName().'/'.$path;

        return $this->getClient()->request($path, $method, $data, $query);
    }

    /**
     * Analyzes a string.
     *
     * Detailed arguments can be found here in the link
     *
     * @param string $text String to be analyzed
     * @param array  $args OPTIONAL Additional arguments
     *
     * @return Awaitable<array> Server response
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-analyze.html
     */
    public async function analyze(string $text, array $args = array()) : Awaitable<array>
    {
        $response = await $this->request('_analyze', Request::POST, $text, $args);
        $data = $response->getData();
        return (array) /* UNSAFE_EXPR */ $data['tokens'];
    }
}
