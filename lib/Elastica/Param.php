<?hh
namespace Elastica;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Class to handle params.
 *
 * This function can be used to handle params for queries, filter, facets
 *
 * @author Nicolas Ruflin <spam@ruflin.com>
 */
class Param implements ArrayableInterface
{
    /**
     * Params.
     *
     * @var <string, mixed>
     */
    protected Map<string, mixed> $_params = Map {};

    /**
     * Raw Params.
     *
     * @var Map<string, mixed>
     */
    protected Map<string, mixed> $_rawParams = Map {};

    /**
     * Converts the params to an array. A default implementation exist to create
     * the an array out of the class name (last part of the class name)
     * and the params.
     *
     * @return array Filter array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $data = array($this->_getBaseName() => $this->getParams());

        if (!empty($this->_rawParams)) {
            $data = array_merge($data, $this->_rawParams);
        }

        return $this->_convertArrayable($data);
    }

    /**
     * Cast objects to arrays.
     *
     * @param array $array
     *
     * @return array
     */
    protected function _convertArrayable(Indexish<string, mixed> $array) : Indexish<string, mixed>
    {
		if ( $array instanceof Map) {
			$arr = Map {};
		} else {
	        $arr = array();
		}

        foreach ($array as $key => $value) {
            if ($value instanceof ArrayableInterface) {
                $arr[$value instanceof NameableInterface ? $value->getName() : $key] = $value->toArray();
            } elseif ($value instanceof Indexish) {
                $arr[$key] = $this->_convertArrayable($value);
            } else {
                $arr[$key] = $value;
            }
        }

        return $arr;
    }

    /**
     * Param's name
     * Picks the last part of the class name and makes it snake_case
     * You can override this method if you want to change the name.
     *
     * @return string name
     */
    protected function _getBaseName() : string
    {
        return Util::getParamName($this);
    }

    /**
     * Sets params not inside params array.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    protected function _setRawParam(string $key, mixed $value) : this
    {
        $this->_rawParams[$key] = $value;

        return $this;
    }

    /**
     * Sets (overwrites) the value at the given key.
     *
     * @param string $key   Key to set
     * @param mixed  $value Key Value
     *
     * @return $this
     */
    public function setParam(string $key, mixed $value) : this
    {
        $this->_params[$key] = $value;

        return $this;
    }

    /**
     * Sets (overwrites) all params of this object.
     *
     * @param array $params Parameter list
     *
     * @return $this
     */
    public function setParams(Map<string, mixed> $params) : this
    {
        $this->_params = $params;

        return $this;
    }

    /**
     * Adds a param to the list.
     *
     * This function can be used to add an array of params
     *
     * @param string $key   Param key
     * @param mixed  $value Value to set
     *
     * @return $this
     */
    public function addParam(string $key, mixed $value) : this
    {
        if ($key != null) {
            if (!isset($this->_params[$key])) {
                $this->_params[$key] = array();
            }

            /* UNSAFE_EXPR */
            $this->_params[$key][] = $value;
        } elseif ($value instanceof :utableMap) {
            $this->_params = $value;
        } else {
            throw new \RuntimeException();
        }

        return $this;
    }

    /**
     * Returns a specific param.
     *
     * @param string $key Key to return
     *
     * @throws \Elastica\Exception\InvalidException If requested key is not set
     *
     * @return mixed Key value
     */
    public function getParam(string $key) : mixed
    {
        if (!$this->hasParam($key)) {
            throw new InvalidException('Param '.$key.' does not exist');
        }

        return $this->_params[$key];
    }

    /**
     * Test if a param is set.
     *
     * @param string $key Key to test
     *
     * @return bool True if the param is set, false otherwise
     */
    public function hasParam(string $key) : bool
    {
        return isset($this->_params[$key]);
    }

    /**
     * Returns the params array.
     *
     * @return array Params
     */
    public function getParams() : Map<string, mixed>
    {
        return clone $this->_params;
    }
}
