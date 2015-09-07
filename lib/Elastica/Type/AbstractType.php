<?hh
namespace Elastica\Type;

use Elastica\Client;
use Elastica\Exception\InvalidException;
use Elastica\Index;
use Elastica\ResultSet;
use Elastica\Search;
use Elastica\SearchableInterface;
use Elastica\Type as BaseType;
use Elastica\Util;

/**
 * Abstract helper class to implement search indices based on models.
 *
 * This abstract model should help creating search index and a subtype
 * with some easy config entries that are overloaded.
 *
 * The following variables have to be set:
 *    - $_indexName
 *    - $_typeName
 *
 * The following variables can be set for additional configuration
 *    - $_mapping: Value type mapping for the given type
 *    - $_indexParams: Parameters for the index
 *
 * @todo Add some settings examples to code
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
abstract class AbstractType implements SearchableInterface
{
    const MAX_DOCS_PER_REQUEST = 1000;

    /**
     * Index name.
     *
     * @var string Index name
     */
    protected string $_indexName = '';

    /**
     * Index name.
     *
     * @var string Index name
     */
    protected string $_typeName = '';

    /**
     * Client.
     *
     * @var \Elastica\Client Client object
     */
    protected Client $_client;

    /**
     * Index.
     *
     * @var \Elastica\Index Index object
     */
    protected Index $_index;

    /**
     * Type.
     *
     * @var \Elastica\Type Type object
     */
    protected BaseType $_type;

    /**
     * Mapping.
     *
     * @var array Mapping
     */
    protected array $_mapping = array();

    /**
     * Index params.
     *
     * @var array Index  params
     */
    protected array $_indexParams = array();

    /**
     * Source.
     *
     * @var bool Source
     */
    protected bool $_source = true;

    /**
     * Creates index object with client connection.
     *
     * Reads index and type name from protected vars _indexName and _typeName.
     * Has to be set in child class
     *
     * @param \Elastica\Client $client OPTIONAL Client object
     *
     * @throws \Elastica\Exception\InvalidException
     */
    public function __construct(?Client $client = null)
    {
        if (!$client) {
            $client = new Client();
        }

        if (empty($this->_indexName)) {
            throw new InvalidException('Index name has to be set');
        }

        if (empty($this->_typeName)) {
            throw new InvalidException('Type name has to be set');
        }

        $this->_client = $client;
        $this->_index = new Index($this->_client, $this->_indexName);
        $this->_type = new BaseType($this->_index, $this->_typeName);
    }

    /**
     * Creates the index and sets the mapping for this type.
     *
     * @param bool $recreate OPTIONAL Recreates the index if true (default = false)
     *
     * @return Awaitable<\Elastica\Response> Response object
     */
    public async function create(bool $recreate = false) : Awaitable<\Elastica\Response>
    {
        await $this->getIndex()->create($this->_indexParams, $recreate);

        $mapping = new Mapping($this->getType());
        $mapping->setProperties($this->_mapping);
        $mapping->setSource(array('enabled' => $this->_source));
        return await $mapping->send();
    }

    /**
     * @param \Elastica\Query $query
     * @param array|int       $options
     *
     * @return \Elastica\Search
     */
    public function createSearch(mixed $query = '', mixed $options = null) : Search
    {
        return $this->getType()->createSearch($query, $options);
    }

    /**
     * Search on the type.
     *
     * @param string|array|\Elastica\Query $query Array with all query data inside or a Elastica\Query object
     *
     * @return Awaitable<\Elastica\ResultSet> ResultSet with all results inside
     *
     * @see \Elastica\SearchableInterface::search
     */
    public function search(mixed $query = '', mixed $options = null) : Awaitable<ResultSet>
    {
        return $this->getType()->search($query, $options = null);
    }

    /**
     * Count docs in the type based on query.
     *
     * @param string|array|\Elastica\Query $query Array with all query data inside or a Elastica\Query object
     *
     * @return Awaitable<int> number of documents matching the query
     *
     * @see \Elastica\SearchableInterface::count
     */
    public function count(mixed $query = '') : Awaitable<int>
    {
        return $this->getType()->count($query);
    }

    /**
     * Returns the search index.
     *
     * @return \Elastica\Index Index object
     */
    public function getIndex() : Index
    {
        return $this->_index;
    }

    /**
     * Returns type object.
     *
     * @return \Elastica\Type Type object
     */
    public function getType() : BaseType
    {
        return $this->_type;
    }

    /**
     * Converts given time to format: 1995-12-31T23:59:59Z.
     *
     * This is the lucene date format
     *
     * @param int $date Date input (could be string etc.) -> must be supported by strtotime
     *
     * @return string Converted date string
     */
    public function convertDate(int $date) : string
    {
        return Util::convertDate($date);
    }
}
