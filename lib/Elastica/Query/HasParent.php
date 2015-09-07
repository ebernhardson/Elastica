<?hh
namespace Elastica\Query;

use Elastica\Query as BaseQuery;
use Indexish;

/**
 * Returns child documents having parent docs matching the query.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-has-parent-query.html
 */
class HasParent extends AbstractQuery
{
    /**
     * Construct HasChild Query.
     *
     * @param string|\Elastica\Query|\Elastica\Query\AbstractQuery $query
     * @param string                                               $type  Parent document type
     */
    public function __construct(mixed $query, string $type)
    {
        $this->setQuery($query);
        $this->setType($type);
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
        return $this->setParam('query', BaseQuery::create($query));
    }

    /**
     * Set type of the parent document.
     *
     * @param string $type Parent document type
     *
     * @return $this
     */
    public function setType(string $type) : this
    {
        return $this->setParam('type', $type);
    }

    /**
     * Sets the scope.
     *
     * @param string $scope Scope
     *
     * @return $this
     */
    public function setScope(string $scope) : this
    {
        return $this->setParam('_scope', $scope);
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
