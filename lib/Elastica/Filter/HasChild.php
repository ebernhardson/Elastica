<?hh
namespace Elastica\Filter;

use Indexish;

/**
 * Returns parent documents having child docs matching the query.
 *
 * @author Fabian Vogler <fabian@equivalence.ch>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-has-child-filter.html
 */
class HasChild extends AbstractFilter
{
    /**
     * Construct HasChild filter.
     *
     * @param string|\Elastica\Query|\Elastica\Filter\AbstractFilter $query Query string or a Elastica\Query object or a filter
     * @param string|\Elastica\Type                                  $type  Child document type
     */
    public function __construct(mixed $query, mixed $type = null)
    {
        $this->setType($type);
        if ($query instanceof AbstractFilter) {
            $this->setFilter($query);
        } else {
            $this->setQuery($query);
        }
    }

    /**
     * Sets query object.
     *
     * @param string|\Elastica\Query|\Elastica\Query\AbstractQuery $query
     *
     * @return $this
     */
    public function setQuery(mixed $query) : this
    {
        return $this->setParam('query', \Elastica\Query::create($query));
    }

    /**
     * Sets the filter object.
     *
     * @param \Elastica\Filter\AbstractFilter $filter
     *
     * @return $this
     */
    public function setFilter(@\Elastica\Filter\AbstractFilter $filter) : this
    {
        return $this->setParam('filter', $filter);
    }

    /**
     * Set type of the child document.
     *
     * @param string|\Elastica\Type $type Child document type
     *
     * @return $this
     */
    public function setType(mixed $type) : this
    {
        if ($type instanceof \Elastica\Type) {
            $type = $type->getName();
        }

        return $this->setParam('type', (string) $type);
    }

    /**
     * Set minimum number of children are required to match for the parent doc to be considered a match.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setMinimumChildrenCount(int $count) : this
    {
        return $this->setParam('min_children', (int) $count);
    }

    /**
     * Set maximum number of children are required to match for the parent doc to be considered a match.
     *
     * @param int $count
     *
     * @return $this
     */
    public function setMaximumChildrenCount(int $count) : this
    {
        return $this->setParam('max_children', (int) $count);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();

        $baseName = $this->_getBaseName();

        if (isset(/* UNSAFE_EXPR */ $array[$baseName]['query'])) {
            /* UNSAFE_EXPR */
            $array[$baseName]['query'] = $array[$baseName]['query']['query'];
        }

        return $array;
    }
}
