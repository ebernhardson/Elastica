<?hh // strict
namespace Elastica\Query;

/**
 * Term query.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
 */
class Term extends AbstractQuery
{
    /**
     * Constructs the Term query object.
     *
     * @param array $term OPTIONAL Calls setTerm with the given $term array
     */
    public function __construct(Map<string, mixed> $term = Map {})
    {
        $this->setRawTerm($term);
    }

    /**
     * Set term can be used instead of addTerm if some more special
     * values for a term have to be set.
     *
     * @param array $term Term array
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
     * @param float        $boost OPTIONAL Boost value (default = 1.0)
     *
     * @return $this
     */
    public function setTerm(string $key, mixed $value, float $boost = 1.0) : this
    {
        return $this->setRawTerm(Map {$key => Map {'value' => $value, 'boost' => $boost}});
    }
}
