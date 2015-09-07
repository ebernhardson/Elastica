<?hh

namespace Elastica;

use Elastica\Bulk\Action;
use Elastica\Connection\ConnectionPoolCallback;
use Elastica\Exception\ConnectionException;
use Elastica\Exception\InvalidException;
use Elastica\Exception\RuntimeException;
use Psr\Log\LoggerInterface;
use Indexish;

/**
 * Client to connect the the elasticsearch server.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Client
{
    /**
     * Config with defaults.
     *
     * log: Set to true, to enable logging, set a string to log to a specific file
     * retryOnConflict: Use in \Elastica\Client::updateDocument
     *
     * @var array
     */
    protected array $_config = array(
        'host' => null,
        'port' => null,
        'path' => null,
        'url' => null,
        'proxy' => null,
        'transport' => null,
        'persistent' => true,
        'timeout' => null,
        'connections' => array(), // host, port, path, timeout, transport, persistent, timeout, config -> (curl, headers, url)
        'roundRobin' => false,
        'log' => false,
        'retryOnConflict' => 0,
    );

    /**
     * @var callback
     */
    protected ?ConnectionPoolCallback $_callback = null;

    /**
     * @var \Elastica\Request
     */
    protected ?Request $_lastRequest = null;

    /**
     * @var Response \Elastica\Response
     */
    protected ?Response $_lastResponse = null;

    /**
     * @var LoggerInterface
     */
    protected ?LoggerInterface $_logger = null;
    /**
     * @var Connection\ConnectionPool
     */
    protected Connection\ConnectionPool $_connectionPool;

    /**
     * Creates a new Elastica client.
     *
     * @param array    $config   OPTIONAL Additional config options
     * @param callback $callback OPTIONAL Callback function which can be used to be notified about errors (for example connection down)
     */
    public function __construct(array $config = array(), ?ConnectionPoolCallback $callback = null)
    {
        $this->_setConfig($config);
        $this->_callback = $callback;
        $this->_initConnections();
    }

    /**
     * Inits the client connections.
     */
    private function _initConnections() : void
    {
        $connections = array();

        foreach ($this->_config['connections'] as $connection) {
            $connections[] = Connection::create($this->_prepareConnectionParams($connection));
        }

        if (isset($this->_config['servers'])) {
            foreach ($this->_config['servers'] as $server) {
                $connections[] = Connection::create($this->_prepareConnectionParams($server));
            }
        }

        // If no connections set, create default connection
        if (empty($connections)) {
            $connections[] = Connection::create($this->_prepareConnectionParams($this->_config));
        }

        if (!isset($this->_config['connectionStrategy'])) {
            if ($this->_config['roundRobin'] === true) {
                $this->_config['connectionStrategy'] = 'RoundRobin';
            } else {
                $this->_config['connectionStrategy'] = 'Simple';
            }
        }

        $strategy = Connection\Strategy\StrategyFactory::create($this->_config['connectionStrategy']);

        $this->_connectionPool = new Connection\ConnectionPool($connections, $strategy, $this->_callback);
    }

    /**
     * Creates a Connection params array from a Client or server config array.
     *
     * @param array $config
     *
     * @return array
     */
    private function _prepareConnectionParams(array $config) : Map<string, mixed>
    {
        $params = Map {'config' => Map {}};
        foreach ($config as $key => $value) {
            if (in_array($key, array('curl', 'headers', 'url'))) {
                $params['config'][$key] = $value;
            } else {
                $params[$key] = $value;
            }
        }

        return $params;
    }

    /**
     * Sets specific config values (updates and keeps default values).
     *
     * @param array $config Params
     *
     * @return $this
     */
    public function setConfig(array $config) : Client
    {
        $this->_setConfig($config);

        return $this;
    }

    private function _setConfig(array $config) : void
    {
        foreach ($config as $key => $value) {
            $this->_config[$key] = $value;
        }
    }

    /**
     * Returns a specific config key or the whole
     * config array if not set.
     *
     * @param string $key Config key
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return array|string Config value
     */
    public function getConfig(string $key = '') : mixed
    {
        if (empty($key)) {
            return $this->_config;
        }

        if (!array_key_exists($key, $this->_config)) {
            throw new InvalidException('Config key is not set: '.$key);
        }

        return $this->_config[$key];
    }

    /**
     * Sets / overwrites a specific config value.
     *
     * @param string $key   Key to set
     * @param mixed  $value Value
     *
     * @return $this
     */
    public function setConfigValue(string $key, mixed $value) : Client
    {
        return $this->setConfig(array($key => $value));
    }

    /**
     * @param array|string $keys    config key or path of config keys
     * @param mixed        $default default value will be returned if key was not found
     *
     * @return mixed
     */
    public function getConfigValue(mixed $keys, mixed $default = null) : mixed
    {
        $value = $this->_config;
        foreach ((array) $keys as $key) {
            if (isset($value[$key])) {
                $value = $value[$key];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Returns the index for the given connection.
     *
     * @param string $name Index name to create connection to
     *
     * @return \Elastica\Index Index for the given name
     */
    public function getIndex(string $name) : Index
    {
        return new Index($this, $name);
    }

    /**
     * Adds a HTTP Header.
     *
     * @param string $header      The HTTP Header
     * @param string $headerValue The HTTP Header Value
     *
     * @throws \Elastica\Exception\InvalidException If $header or $headerValue is not a string
     *
     * @return $this
     */
    public function addHeader(string $header, string $headerValue) : Client
    {
        if (is_string($header) && is_string($headerValue)) {
            $this->_config['headers'][$header] = $headerValue;
        } else {
            throw new InvalidException('Header must be a string');
        }

        return $this;
    }

    /**
     * Remove a HTTP Header.
     *
     * @param string $header The HTTP Header to remove
     *
     * @throws \Elastica\Exception\InvalidException If $header is not a string
     *
     * @return $this
     */
    public function removeHeader(string $header) : Client
    {
        if (is_string($header)) {
            if (array_key_exists($header, $this->_config['headers'])) {
                unset($this->_config['headers'][$header]);
            }
        } else {
            throw new InvalidException('Header must be a string');
        }

        return $this;
    }

    /**
     * Uses _bulk to send documents to the server.
     *
     * Array of \Elastica\Document as input. Index and type has to be
     * set inside the document, because for bulk settings documents,
     * documents can belong to any type and index
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     *
     * @param array|\Elastica\Document[] $docs Array of Elastica\Document
     *
     * @throws \Elastica\Exception\InvalidException If docs is empty
     *
     * @return Awaitable<\Elastica\Bulk\ResponseSet> Response object
     */
    public function updateDocuments(array $docs) : Awaitable<Bulk\ResponseSet>
    {
        if (empty($docs)) {
            throw new InvalidException('Array has to consist of at least one element');
        }

        $bulk = new Bulk($this);

        $bulk->addDocuments($docs, \Elastica\Bulk\Action::OP_TYPE_UPDATE);

        return $bulk->send();
    }

    /**
     * Uses _bulk to send documents to the server.
     *
     * Array of \Elastica\Document as input. Index and type has to be
     * set inside the document, because for bulk settings documents,
     * documents can belong to any type and index
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     *
     * @param array|\Elastica\Document[] $docs Array of Elastica\Document
     *
     * @throws \Elastica\Exception\InvalidException If docs is empty
     *
     * @return \Elastica\Bulk\ResponseSet Response object
     */
    public function addDocuments(array $docs) : Awaitable<Bulk\ResponseSet>
    {
        if (empty($docs)) {
            throw new InvalidException('Array has to consist of at least one element');
        }

        $bulk = new Bulk($this);

        $bulk->addDocuments($docs);

        return $bulk->send();
    }

    /**
     * Update document, using update script. Requires elasticsearch >= 0.19.0.
     *
     * @param int                                       $id      document id
     * @param array|\Elastica\Script|\Elastica\Document $data    raw data for request body
     * @param string                                    $index   index to update
     * @param string                                    $type    type of index to update
     * @param array                                     $options array of query params to use for query. For possible options check es api
     *
     * @return Awaitable<\Elastica\Response>
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-update.html
     */
    public async function updateDocument(string $id, mixed $data, string $index, string $type, array $options = array()) : Awaitable<Response>
    {
        $path = $index.'/'.$type.'/'.$id.'/_update';

        if ($data instanceof Script) {
            $requestData = $data->toArray();
        } elseif ($data instanceof Document) {
            $requestData = array('doc' => $data->getData());

            if ($data->getDocAsUpsert()) {
                $requestData['doc_as_upsert'] = true;
            }

            $docOptions = $data->getOptions(
                array(
                    'version',
                    'version_type',
                    'routing',
                    'percolate',
                    'parent',
                    'fields',
                    'retry_on_conflict',
                    'consistency',
                    'replication',
                    'refresh',
                    'timeout',
                )
            );
		
			foreach ($options as $k => $v) {
				$docOptions[$k] = $v;
			}
			$options = $docOptions;	
            // set fields param to source only if options was not set before
            if ($data instanceof Document && ($data->isAutoPopulate()
                || $this->getConfigValue(array('document', 'autoPopulate'), false))
                && !isset($options['fields'])
            ) {
                $options['fields'] = '_source';
            }
        } else {
            $requestData = (array) $data;
        }

        //If an upsert document exists
        if ($data instanceof AbstractUpdateAction) {
            if ($data->hasUpsert()) {
                $requestData['upsert'] = $data->getUpsert()?->getData();
            }
        }

        if (!isset($options['retry_on_conflict'])) {
            $retryOnConflict = $this->getConfig('retryOnConflict');
            $options['retry_on_conflict'] = $retryOnConflict;
        }

        $response = await $this->request($path, Request::POST, $requestData, $options);

        if ($response->isOk()
            && $data instanceof Document
            && ($data->isAutoPopulate() || $this->getConfigValue(array('document', 'autoPopulate'), false))
        ) {
            $responseData = $response->getData();
            if (isset(/* UNSAFE_EXPR */ $responseData['_version'])) {
                $data->setVersion(/* UNSAFE_EXPR */ $responseData['_version']);
            }
            if (isset($options['fields'])) {
                $this->_populateDocumentFieldsFromResponse($response, $data, (string) $options['fields']);
            }
        }

        return $response;
    }

    /**
     * @param \Elastica\Response $response
     * @param \Elastica\Document $document
     * @param string             $fields   Array of field names to be populated or '_source' if whole document data should be updated
     */
    protected function _populateDocumentFieldsFromResponse(Response $response, Document $document, string $fields) : void
    {
        $responseData = $response->getData();
        if ('_source' == $fields) {
            if (isset(/* UNSAFE_EXPR */ $responseData['get']['_source']) && /* UNSAFE_EXPR */ $responseData['get']['_source'] instanceof Indexish) {
                $document->setData(/* UNSAFE_EXPR */ $responseData['get']['_source']);
            }
        } else {
            $keys = explode(',', $fields);
            $data = $document->getData();
            if (!$data instanceof Indexish) {
                throw new \InvalidArgumentException('expected array');
            }
            foreach ($keys as $key) {
                if (isset(/* UNSAFE_EXPR */ $responseData['get']['fields'][$key])) {
                    $data[$key] = /* UNSAFE_EXPR */ $responseData['get']['fields'][$key];
                } elseif (isset($data[$key])) {
                    unset($data[$key]);
                }
            }
            $document->setData($data);
        }
    }

    /**
     * Bulk deletes documents.
     *
     * @param array|\Elastica\Document[] $docs
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return Awaitable<\Elastica\Bulk\ResponseSet>
     */
    public function deleteDocuments(array $docs) : Awaitable<Bulk\ResponseSet>
    {
        if (empty($docs)) {
            throw new InvalidException('Array has to consist of at least one element');
        }

        $bulk = new Bulk($this);
        $bulk->addDocuments($docs, Action::OP_TYPE_DELETE);

        return $bulk->send();
    }

    /**
     * Returns the status object for all indices.
     *
     * @return Awaitable<\Elastica\Status> Status object
     */
    public function getStatus() : Awaitable<Status>
    {
        return Status::create($this);
    }

    /**
     * Returns the current cluster.
     *
     * @return Awaitable<\Elastica\Cluster> Cluster object
     */
    public function getCluster() : Awaitable<Cluster>
    {
        return Cluster::create($this);
    }

    /**
     * @param \Elastica\Connection $connection
     *
     * @return $this
     */
    public function addConnection(Connection $connection) : Client
    {
        $this->_connectionPool->addConnection($connection);

        return $this;
    }

    /**
     * Determines whether a valid connection is available for use.
     *
     * @return bool
     */
    public function hasConnection() : bool
    {
        return $this->_connectionPool->hasConnection();
    }

    /**
     * @throws \Elastica\Exception\ClientException
     *
     * @return \Elastica\Connection
     */
    public function getConnection() : Connection
    {
        return $this->_connectionPool->getConnection();
    }

    /**
     * @return \Elastica\Connection[]
     */
    public function getConnections() : array<Connection>
    {
        return $this->_connectionPool->getConnections();
    }

    /**
     * @return \Elastica\Connection\Strategy\StrategyInterface
     */
    public function getConnectionStrategy() : Connection\Strategy\StrategyInterface
    {
        return $this->_connectionPool->getStrategy();
    }

    /**
     * @param array|\Elastica\Connection[] $connections
     *
     * @return $this
     */
    public function setConnections(array<Connection> $connections) : Client
    {
        $this->_connectionPool->setConnections($connections);

        return $this;
    }

    /**
     * Deletes documents with the given ids, index, type from the index.
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     *
     * @param array                  $ids     Document ids
     * @param string|\Elastica\Index $index   Index name
     * @param string|\Elastica\Type  $type    Type of documents
     * @param string|false           $routing Optional routing key for all ids
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return Awaitable<\Elastica\Bulk\ResponseSet> Response  object
     */
    public function deleteIds(array $ids, mixed $index, mixed $type, mixed $routing = '') : Awaitable<Bulk\ResponseSet>
    {
        if (empty($ids)) {
            throw new InvalidException('Array has to consist of at least one id');
        }

        $bulk = new Bulk($this);
        $bulk->setIndex($index);
        $bulk->setType($type);

        foreach ($ids as $id) {
            $action = new Action(Action::OP_TYPE_DELETE);
            $action->setId($id);

            if ($routing !== '') {
                $action->setRouting($routing);
            }

            $bulk->addAction($action);
        }

        return $bulk->send();
    }

    /**
     * Bulk operation.
     *
     * Every entry in the params array has to exactly on array
     * of the bulk operation. An example param array would be:
     *
     * array(
     *         array('index' => array('_index' => 'test', '_type' => 'user', '_id' => '1')),
     *         array('user' => array('name' => 'hans')),
     *         array('delete' => array('_index' => 'test', '_type' => 'user', '_id' => '2'))
     * );
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/docs-bulk.html
     *
     * @param array $params Parameter array
     *
     * @throws \Elastica\Exception\ResponseException
     * @throws \Elastica\Exception\InvalidException
     *
     * @return Awaitable<\Elastica\Bulk\ResponseSet> Response object
     */
    public function bulk(array $params) : Awaitable<Bulk\ResponseSet>
    {
        if (empty($params)) {
            throw new InvalidException('Array has to consist of at least one param');
        }

        $bulk = new Bulk($this);

        $bulk->addRawData($params);

        return $bulk->send();
    }

    /**
     * Makes calls to the elasticsearch server based on this index.
     *
     * It's possible to make any REST query directly over this method
     *
     * @param string $path   Path to call
     * @param string $method Rest method to use (GET, POST, DELETE, PUT)
     * @param array  $data   OPTIONAL Arguments as array
     * @param array  $query  OPTIONAL Query params
     *
     * @throws Exception\ConnectionException|\Exception
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public async function request(string $path, string $method = Request::GET, mixed $data = array(), ?Indexish<string, mixed> $query = null) : Awaitable<Response>
    {
		if ($query === null) {
			// this has to be here, rather than in the signature, or phpunit
			// will blow up when mocking.
			$query = array();
		}
        $connection = $this->getConnection();
        try {
            $request = new Request($path, $method, $data, $query, $connection);

            $this->_log($request);

            $response = await $request->send();

            $this->_lastRequest = $request;
            $this->_lastResponse = $response;

            return $response;
        } catch (ConnectionException $e) {
            $this->_connectionPool->onFail($connection, $e, $this);

            // In case there is no valid connection left, throw exception which caused the disabling of the connection.
            if (!$this->hasConnection()) {
                throw $e;
            }

            return await $this->request($path, $method, $data, $query);
        }
    }

    /**
     * Optimizes all search indices.
     *
     * @param array $args OPTIONAL Optional arguments
     *
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-optimize.html
     */
    public function optimizeAll(array $args = array()) : Awaitable<Response>
    {
        return $this->request('_optimize', Request::POST, array(), $args);
    }

    /**
     * Refreshes all search indices.
     *
     * @return Awaitable<\Elastica\Response> Response object
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/indices-refresh.html
     */
    public function refreshAll() : Awaitable<Response>
    {
        return $this->request('_refresh', Request::POST);
    }

    /**
     * logging.
     *
     * @param string|\Elastica\Request $context
     *
     * @throws Exception\RuntimeException
     */
    protected function _log(mixed $context) : void
    {
        $log = $this->getConfig('log');
        if ($log && !class_exists('Psr\Log\AbstractLogger')) {
            throw new RuntimeException('Class Psr\Log\AbstractLogger not found');
        } elseif (!$this->_logger && $log) {
            $this->setLogger(new Log($this->getConfig('log')));
        }
        $logger = $this->_logger;
        if ($logger !== null) {
            if ($context instanceof Request) {
                $data = $context->toArray();
            } else {
                $data = array('message' => $context);
            }
            $logger->debug('logging Request', $data);
        }
    }

    /**
     * This may not be what you expect with async requests.
     * @return \Elastica\Request
     */
    public function getLastRequest() : ?Request
    {
        return $this->_lastRequest;
    }

    /**
     * This may not be what you expect with async requests.
     * @return \Elastica\Response
     */
    public function getLastResponse() : ?Response
    {
        return $this->_lastResponse;
    }

    /**
     * set Logger.
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger) : Client
    {
        $this->_logger = $logger;

        return $this;
    }
}
