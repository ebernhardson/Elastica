<?hh
namespace Elastica\Filter;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Bool Filter.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-filter.html
 */
class BoolFilter extends AbstractFilter
{
    protected array<string, array> $_filters = array(
        'must' => array(),
        'should' => array(),
        'mustNot' => array()
    );

    /**
     * Adds should filter.
     *
     * @param array|\Elastica\Filter\AbstractFilter $args Filter data
     *
     * @return $this
     */
    public function addShould(mixed $args) : this
    {
        return $this->_addFilter('should', $args);
    }

    /**
     * Adds must filter.
     *
     * @param array|\Elastica\Filter\AbstractFilter $args Filter data
     *
     * @return $this
     */
    public function addMust(mixed $args) : this
    {
        return $this->_addFilter('must', $args);
    }

    /**
     * Adds mustNot filter.
     *
     * @param array|\Elastica\Filter\AbstractFilter $args Filter data
     *
     * @return $this
     */
    public function addMustNot(mixed $args) : this
    {
        return $this->_addFilter('mustNot', $args);
    }

    /**
     * Adds general filter based on type.
     *
     * @param string                                $type Filter type
     * @param array|\Elastica\Filter\AbstractFilter $args Filter data
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return $this
     */
    protected function _addFilter(string $type, mixed $args) : this
    {
        if (!$args instanceof Indexish && !($args instanceof AbstractFilter)) {
            throw new InvalidException('Invalid parameter. Has to be array or instance of Elastica\Filter');
        }

        if ($args instanceof Indexish) {
            $parsedArgs = array();

            foreach ($args as $filter) {
                if ($filter instanceof AbstractFilter) {
                    $parsedArgs[] = $filter;
                }
            }

            $args = $parsedArgs;
        }

        $this->_filters[$type][] = $args;

        return $this;
    }

    /**
     * Converts bool filter to array.
     *
     * @see \Elastica\Filter\AbstractFilter::toArray()
     *
     * @return array Filter array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $args = array();

        if (!empty($this->_filters['must'])) {
            $args['bool']['must'] = $this->_filters['must'];
        }

        if (!empty($this->_filters['should'])) {
            $args['bool']['should'] = $this->_filters['should'];
        }

        if (!empty($this->_filters['mustNot'])) {
            $args['bool']['must_not'] = $this->_filters['mustNot'];
        }

        if (isset($args['bool'])) {
            $params = clone $this->getParams();
            foreach ($args['bool'] as $k => $v) {
                if (!$params->contains($k)) {
                    $params->set($k, $v);
                }
            }
            $args['bool'] = $params;
        }

        return $this->_convertArrayable($args);
    }
}
