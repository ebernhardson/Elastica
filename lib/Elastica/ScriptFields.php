<?hh // strict
namespace Elastica;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Container for scripts as fields.
 *
 * @author Sebastien Lavoie <github@lavoie.sl>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-request-script-fields.html
 */
class ScriptFields extends Param
{
    /**
     * @param \Elastica\Script[]|array $scripts OPTIONAL
     */
    public function __construct(Indexish<string, Script> $scripts = array())
    {
        if (count($scripts) > 0) {
            $this->setScripts($scripts);
        }
    }

    /**
     * @param string           $name   Name of the Script field
     * @param \Elastica\Script $script
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return $this
     */
    public function addScript(string $name, Script $script) : this
    {
        if (!is_string($name) || !strlen($name)) {
            throw new InvalidException('The name of a Script is required and must be a string');
        }
        $this->setParam($name, $script);

        return $this;
    }

    /**
     * @param \Elastica\Script[]|array $scripts Associative array of string => Elastica\Script
     *
     * @return $this
     */
    public function setScripts(Indexish<string, Script> $scripts) : this
    {
        $this->_params = Map {};
        foreach ($scripts as $name => $script) {
            $this->addScript($name, $script);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        return $this->_convertArrayable($this->_params);
    }
}
