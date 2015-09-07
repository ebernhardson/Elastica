<?hh
namespace Elastica\Filter;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Geo distance filter.
 *
 * @author munkie
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-geo-distance-range-filter.html
 */
class GeoDistanceRange extends AbstractGeoDistance
{
    const RANGE_FROM = 'from';
    const RANGE_TO = 'to';
    const RANGE_LT = 'lt';
    const RANGE_LTE = 'lte';
    const RANGE_GT = 'gt';
    const RANGE_GTE = 'gte';

    const RANGE_INCLUDE_LOWER = 'include_lower';
    const RANGE_INCLUDE_UPPER = 'include_upper';

    /**
     * @var array
     */
    protected array $_ranges = array();

    /**
     * @param string       $key
     * @param array|string $location
     * @param array        $ranges
     *
     * @internal param string $distance
     */
    public function __construct(string $key, mixed $location, array $ranges = array())
    {
        parent::__construct($key, $location);

        if (!empty($ranges)) {
            $this->setRanges($ranges);
        }
    }

    /**
     * @param array $ranges
     *
     * @return $this
     */
    public function setRanges(array $ranges) : this
    {
        $this->_ranges = array();

        foreach ($ranges as $key => $value) {
            $this->setRange($key, $value);
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return $this
     */
    public function setRange(string $key, mixed $value) : this
    {
        switch ($key) {
            case self::RANGE_TO:
            case self::RANGE_FROM:
            case self::RANGE_GT:
            case self::RANGE_GTE:
            case self::RANGE_LT:
            case self::RANGE_LTE:
                break;
            case self::RANGE_INCLUDE_LOWER:
            case self::RANGE_INCLUDE_UPPER:
                $value = (bool) $value;
                break;
            default:
                throw new InvalidException('Invalid range parameter given: '.$key);
        }
        $this->_ranges[$key] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        foreach ($this->_ranges as $key => $value) {
            $this->setParam($key, $value);
        }

        return parent::toArray();
    }
}
