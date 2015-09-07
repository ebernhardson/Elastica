<?hh // strict
namespace Elastica\Query;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Bool query.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
 */
class BoolQuery extends AbstractQuery
{
    /**
     * Add should part to query.
     *
     * @param \Elastica\Query\AbstractQuery|array $args Should query
     *
     * @return $this
     */
    public function addShould(mixed $args) : this
    {
        return $this->_addQuery('should', $args);
    }

    /**
     * Add must part to query.
     *
     * @param \Elastica\Query\AbstractQuery|array $args Must query
     *
     * @return $this
     */
    public function addMust(mixed $args) : this
    {
        return $this->_addQuery('must', $args);
    }

    /**
     * Add must not part to query.
     *
     * @param \Elastica\Query\AbstractQuery|array $args Must not query
     *
     * @return $this
     */
    public function addMustNot(mixed $args) : this
    {
        return $this->_addQuery('must_not', $args);
    }

    /**
     * Adds a query to the current object.
     *
     * @param string                              $type Query type
     * @param \Elastica\Query\AbstractQuery|array $args Query
     *
     * @throws \Elastica\Exception\InvalidException If not valid query
     *
     * @return $this
     */
    protected function _addQuery(string $type, mixed $args) : this
    {
        if (!$args instanceof Indexish && !($args instanceof AbstractQuery)) {
            throw new InvalidException('Invalid parameter. Has to be array or instance of Elastica\Query\AbstractQuery');
        }

        return $this->addParam($type, $args);
    }

    /**
     * Sets boost value of this query.
     *
     * @param float $boost Boost value
     *
     * @return $this
     */
    public function setBoost(float $boost) : this
    {
        return $this->setParam('boost', $boost);
    }

    /**
     * Set the minimum number of of should match.
     *
     * @param int $minimumNumberShouldMatch Should match minimum
     *
     * @return $this
     */
    public function setMinimumNumberShouldMatch(int $minimumNumberShouldMatch) : this
    {
        return $this->setParam('minimum_number_should_match', $minimumNumberShouldMatch);
    }
}
