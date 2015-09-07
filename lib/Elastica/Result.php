<?hh
namespace Elastica;

/**
 * Elastica result item.
 *
 * Stores all information from a result
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Result
{
    /**
     * Hit array.
     *
     * @var array Hit array
     */
    protected array $_hit = array();

    /**
     * Constructs a single results object.
     *
     * @param array $hit Hit data
     */
    public function __construct(array $hit)
    {
        $this->_hit = $hit;
    }

    /**
     * Returns a param from the result hit array.
     *
     * This function can be used to retrieve all data for which a specific
     * function doesn't exist.
     * If the param does not exist, an empty array is returned
     *
     * @param string $name Param name
     *
     * @return string|array data
     */
    public function getParam(string $name) : mixed
    {
        if (isset($this->_hit[$name])) {
            return $this->_hit[$name];
        }

        return array();
    }

    /**
     * Test if a param from the result hit is set.
     *
     * @param string $name Param name to test
     *
     * @return bool True if the param is set, false otherwise
     */
    public function hasParam(string $name) : bool
    {
        return isset($this->_hit[$name]);
    }

    /**
     * Returns the hit id.
     *
     * @return string Hit id
     */
    public function getId() : string
    {
        return (string) $this->getParam('_id');
    }

    /**
     * Returns the type of the result.
     *
     * @return string Result type
     */
    public function getType() : string
    {
        return (string) $this->getParam('_type');
    }

    /**
     * Returns list of fields.
     *
     * @return array Fields list
     */
    public function getFields() : array
    {
        return (array) $this->getParam('fields');
    }

    /**
     * Returns whether result has fields.
     *
     * @return bool
     */
    public function hasFields() : bool
    {
        return (bool) $this->hasParam('fields');
    }

    /**
     * Returns the index name of the result.
     *
     * @return string Index name
     */
    public function getIndex() : string
    {
        return (string) $this->getParam('_index');
    }

    /**
     * Returns the score of the result.
     *
     * @return float Result score
     */
    public function getScore() : float
    {
        return (float) $this->getParam('_score');
    }

    /**
     * Returns the raw hit array.
     *
     * @return array Hit array
     */
    public function getHit() : array
    {
        return (array) $this->_hit;
    }

    /**
     * Returns the version information from the hit.
     *
     * @return string|int Document version
     */
    public function getVersion() : mixed
    {
        return $this->getParam('_version');
    }

    /**
     * Returns result data.
     *
     * Checks for partial result data with getFields, falls back to getSource
     *
     * @return array Result data array
     */
    public function getData() : array
    {
        if (isset($this->_hit['fields']) && !isset($this->_hit['_source'])) {
            return $this->getFields();
        }

        return $this->getSource();
    }

    /**
     * Returns the result source.
     *
     * @return array Source data array
     */
    public function getSource() : array
    {
        return (array) $this->getParam('_source');
    }

    /**
     * Returns result data.
     *
     * @return array Result data array
     */
    public function getHighlights() : array
    {
        return (array) $this->getParam('highlight');
    }

    /**
     * Returns explanation on how its score was computed.
     *
     * @return array explanations
     */
    public function getExplanation() : array
    {
        return (array) $this->getParam('_explanation');
    }

    /**
     * Magic function to directly access keys inside the result.
     *
     * Returns null if key does not exist
     *
     * @param string $key Key name
     *
     * @return mixed Key value
     */
    public function __get(string $key) : mixed
    {
        $source = $this->getData();

        return array_key_exists($key, $source) ? $source[$key] : null;
    }

    /**
     * Magic function to support isset() calls.
     *
     * @param string $key Key name
     *
     * @return bool
     */
    public function __isset(string $key) : bool
    {
        $source = $this->getData();

        return array_key_exists($key, $source) && $source[$key] !== null;
    }
}
