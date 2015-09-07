<?hh
namespace Elastica\Multi;

use Elastica\Exception\InvalidException;
use Elastica\Response;
use Elastica\ResultSet as BaseResultSet;
use Elastica\Search as BaseSearch;
use Indexish;

/**
 * Elastica multi search result set
 * List of result sets for each search request.
 *
 * @author munkie
 */
class ResultSet implements \Iterator<BaseResultSet>, \ArrayAccess<mixed, BaseResultSet>, \Countable
{
    /**
     * Result Sets.
     *
     * @var array|\Elastica\ResultSet[] Result Sets
     */
    protected array $_resultSets = array();

    /**
     * Current position.
     *
     * @var int Current position
     */
    protected int $_position = 0;

    /**
     * Response.
     *
     * @var \Elastica\Response Response object
     */
    protected Response $_response;

    /**
     * Constructs ResultSet object.
     *
     * @param \Elastica\Response       $response
     * @param array|\Elastica\Search[] $searches
     */
    public function __construct(Response $response, array $searches)
    {
        $this->_response = $response;
        $this->rewind();
        $this->_init($response, $searches);
    }

    /**
     * @param \Elastica\Response       $response
     * @param array|\Elastica\Search[] $searches
     *
     * @throws \Elastica\Exception\InvalidException
     */
    protected function _init(Response $response, array $searches) : void
    {
        $this->_response = $response;
        $responseData = $response->getData();

        if (isset(/* UNSAFE_EXPR */ $responseData['responses']) && /* UNSAFE_EXPR */ $responseData['responses'] instanceof Indexish) {
            reset($searches);
            foreach (/* UNSAFE_EXPR */ $responseData['responses'] as $key => $responseData) {
                $currentSearch = each($searches);

                if ($currentSearch === false) {
                    throw new InvalidException('No result found for search #'.$key);
                } elseif (!$currentSearch['value'] instanceof BaseSearch) {
                    throw new InvalidException('Invalid object for search #'.$key.' provided. Should be Elastica\Search');
                }

                $search = $currentSearch['value'];
                $query = $search->getQuery();

                $response = new Response($responseData);
                $this->_resultSets[$currentSearch['key']] = new BaseResultSet($response, $query);
            }
        }
    }

    /**
     * @return array|\Elastica\ResultSet[]
     */
    public function getResultSets() : array
    {
        return $this->_resultSets;
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
     * There is at least one result set with error.
     *
     * @return bool
     */
    public function hasError() : bool
    {
        foreach ($this->getResultSets() as $resultSet) {
            if ($resultSet->getResponse()->hasError()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool|\Elastica\ResultSet
     */
    public function current() : BaseResultSet
    {
        if (!$this->valid()) {
            throw new OutOfBoundsException();
        }
        return $this->_resultSets[$this->key()];
    }

    /**
     */
    public function next() : void
    {
        ++$this->_position;
    }

    /**
     * @return int
     */
    public function key() : int
    {
        return $this->_position;
    }

    /**
     * @return bool
     */
    public function valid() : bool
    {
        return isset($this->_resultSets[$this->key()]);
    }

    /**
     */
    public function rewind() : void
    {
        $this->_position = 0;
    }

    /**
     * @return int
     */
    public function count() : int
    {
        return count($this->_resultSets);
    }

    /**
     * @param string|int $offset
     *
     * @return bool true on success or false on failure.
     */
    public function offsetExists($offset)
    {
        return isset($this->_resultSets[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return ResultSet Can return all value types.
     */
    public function offsetGet($offset)
    {
        if (!isset($this->_resultSets[$offset])) {
            throw new \OutOfBoundsException();
        }
        return $this->_resultSets[$offset];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->_resultSets[] = $value;
        } else {
            $this->_resultSets[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->_resultSets[$offset]);
    }
}
