<?hh
namespace Elastica\Filter;

use Indexish;

/**
 * Range Filter.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-range-filter.html
 */
class Range extends AbstractFilter
{
    /**
     * Fields.
     *
     * @var array Fields
     */
    protected array $_fields = array();

    /**
     * Construct range filter.
     *
     * @param string $fieldName Field name
     * @param array  $args      Field arguments
     */
    public function __construct(string $fieldName = '', array $args = array())
    {
        if ($fieldName) {
            $this->addField($fieldName, $args);
        }
    }

    /**
     * Ads a field with arguments to the range query.
     *
     * @param string $fieldName Field name
     * @param array  $args      Field arguments
     *
     * @return $this
     */
    public function addField(string $fieldName, array $args) : this
    {
        $this->_fields[$fieldName] = $args;

        return $this;
    }

    /**
     * Set execution mode.
     *
     * @param string $execution Options: "index" or "fielddata"
     *
     * @return $this
     */
    public function setExecution(string $execution) : this
    {
        return $this->setParam('execution', (string) $execution);
    }

    /**
     * Converts object to array.
     *
     * @see \Elastica\Filter\AbstractFilter::toArray()
     *
     * @return array Filter array
     */
    public function toArray() : Indexish<string, mixed>
    {
		$params = $this->getParams();
		foreach ($this->_fields as $k => $v) {
			$params[$k] = $v;
		}
        $this->setParams($params);

        return parent::toArray();
    }
}
