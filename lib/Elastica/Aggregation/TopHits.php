<?hh
namespace Elastica\Aggregation;

use Elastica\Script;
use Elastica\ScriptFields;
use Indexish;

/**
 * Class TopHits.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-top-hits-aggregation.html
 */
class TopHits extends AbstractAggregation
{
    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();

        // if there are no params, it's ok, but ES will throw exception if json
        // will be like {"top_hits":[]} instead of {"top_hits":{}}
        if (empty($array['top_hits'])) {
            $array['top_hits'] = new \stdClass();
        }

        return $array;
    }

    /**
     * The maximum number of top matching hits to return per bucket. By default the top three matching hits are returned.
     *
     * @param int $size
     *
     * @return $this
     */
    public function setSize(int $size) : this
    {
        return $this->setParam('size', (int) $size);
    }

    /**
     * The offset from the first result you want to fetch.
     *
     * @param int $from
     *
     * @return $this
     */
    public function setFrom(int $from) : this
    {
        return $this->setParam('from', (int) $from);
    }

    /**
     * How the top matching hits should be sorted. By default the hits are sorted by the score of the main query.
     *
     * @param array $sortArgs
     *
     * @return $this
     */
    public function setSort(array $sortArgs) : this
    {
        return $this->setParam('sort', $sortArgs);
    }

    /**
     * Allows to control how the _source field is returned with every hit.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function setSource(array $fields) : this
    {
        return $this->setParam('_source', $fields);
    }

    /**
     * Returns a version for each search hit.
     *
     * @param bool $version
     *
     * @return $this
     */
    public function setVersion(bool $version) : this
    {
        return $this->setParam('version', (bool) $version);
    }

    /**
     * Enables explanation for each hit on how its score was computed.
     *
     * @param bool $explain
     *
     * @return $this
     */
    public function setExplain(bool $explain) : this
    {
        return $this->setParam('explain', (bool) $explain);
    }

    /**
     * Set script fields.
     *
     * @param array|\Elastica\ScriptFields $scriptFields
     *
     * @return $this
     */
    public function setScriptFields(mixed $scriptFields) : this
    {
        if ($scriptFields instanceof Indexish) {
            $scriptFields = new ScriptFields($scriptFields);
        }

        return $this->setParam('script_fields', $scriptFields);
    }

    /**
     * Adds a Script to the aggregation.
     *
     * @param string           $name
     * @param \Elastica\Script $script
     *
     * @return $this
     */
    public function addScriptField(string $name, Script $script) : this
    {
        if (!isset($this->_params['script_fields'])) {
            $this->_params['script_fields'] = new ScriptFields();
        }

        /* UNSAFE_EXPR */
        $this->_params['script_fields']->addScript($name, $script);

        return $this;
    }

    /**
     * Sets highlight arguments for the results.
     *
     * @param array $highlightArgs
     *
     * @return $this
     */
    public function setHighlight(array $highlightArgs) : this
    {
        return $this->setParam('highlight', $highlightArgs);
    }

    /**
     * Allows to return the field data representation of a field for each hit.
     *
     * @param array $fields
     *
     * @return $this
     */
    public function setFieldDataFields(array $fields) : this
    {
        return $this->setParam('fielddata_fields', $fields);
    }
}
