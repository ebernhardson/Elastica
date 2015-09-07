<?hh
namespace Elastica;

use Elastica\Exception\InvalidException;
use Indexish;

/**
 * Script objects, containing script internals.
 *
 * @author avasilenko <aa.vasilenko@gmail.com>
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/modules-scripting.html
 */
class Script extends AbstractScript
{
    const LANG_MVEL = 'mvel';
    const LANG_JS = 'js';
    const LANG_GROOVY = 'groovy';
    const LANG_PYTHON = 'python';
    const LANG_NATIVE = 'native';

    /**
     * @var string
     */
    private string $_script;

    /**
     * @var string
     */
    private $_lang;

    /**
     * @param string      $script
     * @param array|null  $params
     * @param string|null $lang
     * @param string|null $id
     */
    public function __construct(string $script, ?Map<string, mixed> $params = null, ?string $lang = null, ?string $id = null)
    {
        parent::__construct($params, $id);

        $this->_script = $script;

        if ($lang !== null) {
            $this->setLang($lang);
        }
    }

    /**
     * @param string $lang
     *
     * @return $this
     */
    public function setLang(string $lang) : this
    {
        $this->_lang = $lang;

        return $this;
    }

    /**
     * @return string
     */
    public function getLang() : string
    {
        return $this->_lang;
    }

    /**
     * @param string $script
     *
     * @return $this
     */
    public function setScript(string $script) : this
    {
        $this->_script = $script;

        return $this;
    }

    /**
     * @return string
     */
    public function getScript() : string
    {
        return $this->_script;
    }

    /**
     * @param string|array|\Elastica\Script $data
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return self
     */
    public static function create(mixed $data) : Script
    {
        if ($data instanceof self) {
            $script = $data;
        } elseif ($data instanceof Indexish) {
            $script = self::_createFromArray($data);
        } elseif (is_string($data)) {
            $script = new self($data);
        } else {
            throw new InvalidException('Failed to create script. Invalid data passed.');
        }

        return $script;
    }

    /**
     * @param array $data
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return self
     */
    protected static function _createFromArray(Indexish<string, mixed> $data) : Script
    {
        if (!isset($data['script'])) {
            throw new InvalidException("\$data['script'] is required");
        }

        $script = new self((string) $data['script']);

        if (isset($data['lang'])) {
            $script->setLang((string) $data['lang']);
        }

        if (isset($data['params'])) {
            $params = $data['params'];
            if (!$params instanceof Map) {
                throw new InvalidException("\$data['params'] should be Map");
            }
            $script->setParams($params);
        }

        return $script;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = array(
            'script' => $this->_script,
        );

        if (!empty($this->_params)) {
            $array['params'] = $this->_convertArrayable($this->_params);
        }

        if ($this->_lang) {
            $array['lang'] = $this->_lang;
        }

        return $array;
    }
}
