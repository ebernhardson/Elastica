<?hh // strict
namespace Elastica\Filter;

/**
 * geo_shape filter.
 *
 * Filter pre-indexed shape definitions
 *
 * @author Bennie Krijger <benniekrijger@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-shape-filter.html
 */
abstract class AbstractGeoShape extends AbstractFilter
{
    const RELATION_INTERSECT = 'intersects';
    const RELATION_DISJOINT = 'disjoint';
    const RELATION_CONTAINS = 'within';

    /**
     * @var string
     *
     * elasticsearch path of the pre-indexed shape
     */
    protected ?string $_path;

    /**
     * @var string
     *
     * the relation of the 2 shaped: intersects, disjoint, within
     */
    protected string $_relation = self::RELATION_INTERSECT;

    /**
     * @param string $relation
     *
     * @return $this
     */
    public function setRelation(string $relation) : this
    {
        $this->_relation = $relation;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelation() : string
    {
        return $this->_relation;
    }
}
