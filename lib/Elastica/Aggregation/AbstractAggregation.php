<?hh
namespace Elastica\Aggregation;

use Elastica\Aggregation\AbstractAggregation;
use Elastica\Exception\InvalidException;
use Elastica\NameableInterface;
use Elastica\Param;
use Indexish;

abstract class AbstractAggregation extends Param implements NameableInterface
{
    /**
     * @var string The name of this aggregation
     */
    protected $_name;

    /**
     * @var array Subaggregations belonging to this aggregation
     */
    protected array $_aggs = array();

    /**
     * @param string $name the name of this aggregation
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }

    /**
     * Set the name of this aggregation.
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name) : this
    {
        $this->_name = $name;

        return $this;
    }

    /**
     * Retrieve the name of this aggregation.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->_name;
    }

    /**
     * Retrieve all subaggregations belonging to this aggregation.
     *
     * @return array
     */
    public function getAggs() : array
    {
        return $this->_aggs;
    }

    /**
     * Add a sub-aggregation.
     *
     * @param AbstractAggregation $aggregation
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return $this
     */
    public function addAggregation(AbstractAggregation $aggregation) : AbstractAggregation
    {
        if ($aggregation instanceof GlobalAggregation) {
            throw new InvalidException('Global aggregators can only be placed as top level aggregators');
        }

        $this->_aggs[] = $aggregation;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();

        if (array_key_exists('global_aggregation', $array)) {
            // compensate for class name GlobalAggregation
            $array = array('global' => new \stdClass());
        }
        if (count($this->_aggs)) {
            $array['aggs'] = $this->_convertArrayable($this->_aggs);
        }

        return $array;
    }
}
