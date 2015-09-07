<?hh // strict
namespace Elastica\Filter;

/**
 * Missing Filter.
 *
 * @author Maciej Wiercinski <maciej@wiercinski.net>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-missing-filter.html
 */
class Missing extends AbstractFilter
{
    /**
     * Construct missing filter.
     *
     * @param string $field OPTIONAL
     */
    public function __construct(string $field = '')
    {
        if (strlen($field)) {
            $this->setField($field);
        }
    }

    /**
     * Set field.
     *
     * @param string $field
     *
     * @return $this
     */
    public function setField(string $field) : this
    {
        return $this->setParam('field', (string) $field);
    }

    /**
     * Set "existence" parameter.
     *
     * @param bool $existence
     *
     * @return $this
     */
    public function setExistence(bool $existence) : this
    {
        return $this->setParam('existence', (bool) $existence);
    }

    /**
     * Set "null_value" parameter.
     *
     * @param bool $nullValue
     *
     * @return $this
     */
    public function setNullValue(bool $nullValue) : this
    {
        return $this->setParam('null_value', (bool) $nullValue);
    }
}
