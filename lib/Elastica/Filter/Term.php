<?hh // strict
namespace Elastica\Filter;

/**
 * Term query.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-filter.html
 */
class Term extends AbstractFilter
{
    /**
     * Construct term filter.
     *
     * @param array $term Term array
     */
    public function __construct(Map<string, mixed> $term = Map {})
    {
        $this->setRawTerm($term);
    }

    /**
     * Sets/overwrites key and term directly.
     *
     * @param array $term Key value pair
     *
     * @return $this
     */
    public function setRawTerm(Map<string, mixed> $term) : this
    {
        return $this->setParams($term);
    }

    /**
     * Adds a term to the term query.
     *
     * @param string       $key   Key to query
     * @param string|array $value Values(s) for the query. Boost can be set with array
     *
     * @return $this
     */
    public function setTerm(string $key, mixed $value) : this
    {
        return $this->setRawTerm(Map {$key => $value});
    }
}
