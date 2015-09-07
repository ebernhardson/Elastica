<?hh // strict
namespace Elastica\Query;

/**
 * Prefix query.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html
 */
class Prefix extends AbstractQuery
{
    /**
     * Constructs the Prefix query object.
     *
     * @param array $prefix OPTIONAL Calls setRawPrefix with the given $prefix array
     */
    public function __construct(Map<string, mixed> $prefix = Map {})
    {
        $this->setRawPrefix($prefix);
    }

    /**
     * setRawPrefix can be used instead of setPrefix if some more special
     * values for a prefix have to be set.
     *
     * @param array $prefix Prefix array
     *
     * @return $this
     */
    public function setRawPrefix(Map<string, mixed> $prefix) : this
    {
        return $this->setParams($prefix);
    }

    /**
     * Adds a prefix to the prefix query.
     *
     * @param string       $key   Key to query
     * @param string|array $value Values(s) for the query. Boost can be set with array
     * @param float        $boost OPTIONAL Boost value (default = 1.0)
     *
     * @return $this
     */
    public function setPrefix(string $key, mixed $value, float $boost = 1.0) : this
    {
        return $this->setRawPrefix(Map {$key => Map {'value' => $value, 'boost' => $boost}});
    }
}
