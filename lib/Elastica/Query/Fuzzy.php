<?hh
namespace Elastica\Query;

use Elastica\Exception\InvalidException;

/**
 * Fuzzy query.
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html
 */
class Fuzzy extends AbstractQuery
{
    /**
     * Construct a fuzzy query.
     *
     * @param string $fieldName Field name
     * @param string $value     String to search for
     */
    public function __construct(?string $fieldName = null, ?string $value = null)
    {
        if ($fieldName && $value) {
            $this->setField($fieldName, $value);
        }
    }

    /**
     * Set field for fuzzy query.
     *
     * @param string $fieldName Field name
     * @param string $value     String to search for
     *
     * @return $this
     */
    public function setField(string $fieldName, string $value) : this
    {
        if (!is_string($value) || !is_string($fieldName)) {
            throw new InvalidException('The field and value arguments must be of type string.');
        }
        if (count($this->getParams()) > 0 && array_shift(array_keys($this->getParams())) != $fieldName) {
            throw new InvalidException('Fuzzy query can only support a single field.');
        }

        return $this->setParam($fieldName, array('value' => $value));
    }

    /**
     * Set optional parameters on the existing query.
     *
     * @param string $param option name
     * @param mixed  $value Value of the parameter
     *
     * @return $this
     */
    public function setFieldOption(string $param, mixed $value) : this
    {
        //Retrieve the single existing field for alteration.
        $params = $this->getParams();
        if (count($params) < 1) {
            throw new InvalidException('No field has been set');
        }
        $keyArray = array_keys($params);
        /* UNSAFE_EXPR */
        $params[$keyArray[0]][$param] = $value;

        return $this->setParam($keyArray[0], $params[$keyArray[0]]);
    }

    /**
     * Deprecated method of setting a field.
     *
     * @deprecated
     */
    public function addField(@string $fieldName, @array $args) : this
    {
        if (!array_key_exists('value', $args)) {
            throw new InvalidException('Fuzzy query can only support a single field.');
        }
        $this->setField($fieldName, $args['value']);
        unset($args['value']);
        foreach ($args as $param => $value) {
            $this->setFieldOption($param, $value);
        }

        return $this;
    }
}
