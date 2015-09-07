<?hh
namespace Elastica;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Script objects, containing script internals.
 *
 * @author avasilenko <aa.vasilenko@gmail.com>
 * @author Nicolas Assing <nicolas.assing@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting.html
 */
class ScriptFile extends AbstractScript
{
    /**
     * @var string
     */
    private string $_scriptFile;

    /**
     * @param string     $scriptFile
     * @param array|null $params
     * @param null       $id
     */
    public function __construct(string $scriptFile, ?Map<string, mixed> $params = null, $id = null)
    {
        parent::__construct($params, $id);
        $this->_scriptFile = $scriptFile;
    }

    /**
     * @param string $scriptFile
     *
     * @return $this
     */
    public function setScriptFile(string $scriptFile) : this
    {
        $this->_scriptFile = $scriptFile;

        return $this;
    }

    /**
     * @return string
     */
    public function getScriptFile() : string
    {
        return $this->_scriptFile;
    }

    /**
     * @param string|array|\Elastica\Script $data
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return self
     */
    public static function create(mixed $data) : ScriptFile
    {
        if ($data instanceof self) {
            $scriptFile = $data;
        } elseif ($data instanceof Indexish) {
            $scriptFile = self::_createFromArray($data);
        } elseif (is_string($data)) {
            $scriptFile = new self($data);
        } else {
            throw new InvalidException('Failed to create scriptFile. Invalid data passed.');
        }

        return $scriptFile;
    }

    /**
     * @param array $data
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return self
     */
    protected static function _createFromArray(Indexish<string, mixed> $data) : ScriptFile
    {
        if (!isset($data['script_file'])) {
            throw new InvalidException("\$data['script_file'] is required");
        }

        $scriptFile = new self((string) $data['script_file']);

        if (isset($data['params'])) {
            $params = $data['params'];
            if (!$params instanceof Map) {
                throw new InvalidException("\$data['params'] should be array");
            }
            $scriptFile->setParams($params);
        }

        return $scriptFile;
    }

    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = array(
            'script_file' => $this->_scriptFile,
        );

        if (!empty($this->_params)) {
            $array['params'] = $this->_params;
        }

        return $array;
    }
}
