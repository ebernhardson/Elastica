<?hh
namespace Elastica\Bulk;

use Elastica\Bulk;
use Elastica\Index;
use Elastica\JSON;
use Elastica\Type;
use Indexish;

class Action
{
    const OP_TYPE_CREATE = 'create';
    const OP_TYPE_INDEX = 'index';
    const OP_TYPE_DELETE = 'delete';
    const OP_TYPE_UPDATE = 'update';

    /**
     * @var array
     */
    public static array<string> $opTypes = array(
        self::OP_TYPE_CREATE,
        self::OP_TYPE_INDEX,
        self::OP_TYPE_DELETE,
        self::OP_TYPE_UPDATE,
    );

    /**
     * @var string
     */
    protected string $_opType;

    /**
     * @var array
     */
    protected Indexish<string, mixed> $_metadata;

    /**
     * @var array|string
     */
    protected mixed $_source;

    /**
     * @param string        $opType
     * @param array         $metadata
     * @param array|string  $source
     */
    public function __construct(string $opType = self::OP_TYPE_INDEX, array $metadata = array(), mixed $source = array())
    {
        $this->_opType = $opType;
        $this->_metadata = $metadata;
        $this->_source = $source;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setOpType(string $type) : this
    {
        $this->_opType = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getOpType() : string
    {
        return $this->_opType;
    }

    /**
     * @param array $metadata
     *
     * @return $this
     */
    public function setMetadata(Indexish<string, mixed> $metadata) : this
    {
        $this->_metadata = $metadata;

        return $this;
    }

    /**
     * @return array
     */
    public function getMetadata() : Indexish<string, mixed>
    {
        return $this->_metadata;
    }

    /**
     * @return array
     */
    public function getActionMetadata() : array
    {
        return array($this->_opType => $this->getMetadata());
    }

    /**
     * @param array $source
     *
     * @return $this
     */
    public function setSource(mixed $source) : this
    {
        $this->_source = $source;

        return $this;
    }

    /**
     * @return array
     */
    public function getSource() : mixed
    {
        return $this->_source;
    }

    /**
     * @return bool
     */
    public function hasSource() : bool
    {
        return !empty($this->_source);
    }

    /**
     * @param string|\Elastica\Index $index
     *
     * @return $this
     */
    public function setIndex(mixed $index) : this
    {
        if ($index instanceof Index) {
            $index = $index->getName();
        }
        $this->_metadata['_index'] = $index;

        return $this;
    }

    /**
     * @param string|\Elastica\Type $type
     *
     * @return $this
     */
    public function setType(mixed $type) : this
    {
        if ($type instanceof Type) {
            $this->setIndex($type->getIndex()->getName());
            $type = $type->getName();
        }
        $this->_metadata['_type'] = $type;

        return $this;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId(string $id) : this
    {
        $this->_metadata['_id'] = $id;

        return $this;
    }

    /**
     * @param string|false $routing
     *
     * @return $this
     */
    public function setRouting(mixed $routing) : this
    {
        $this->_metadata['_routing'] = $routing;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray() : array
    {
        $data = array($this->getActionMetadata());
        if ($this->hasSource()) {
            $data[] = $this->getSource();
        }

        return $data;
    }

    /**
     * @return string
     */
    public function toString() : string
    {
        $string = JSON::stringify($this->getActionMetadata(), JSON_FORCE_OBJECT).Bulk::DELIMITER;
        if ($this->hasSource()) {
            $source = $this->getSource();
            if (is_string($source)) {
                $string .= $source;
            } elseif ($source instanceof Indexish && array_key_exists('doc', $source) && is_string($source['doc'])) {
                $docAsUpsert = (isset($source['doc_as_upsert'])) ? ', "doc_as_upsert": '.$source['doc_as_upsert'] : '';
                $string .= '{"doc": '.$source['doc'].$docAsUpsert.'}';
            } else {
                $string .= JSON::stringify($source, 'JSON_ELASTICSEARCH');
            }
            $string .= Bulk::DELIMITER;
        }

        return $string;
    }

    /**
     * @param string $opType
     *
     * @return bool
     */
    public static function isValidOpType(mixed $opType) : bool
    {
        return is_string($opType) && in_array($opType, self::$opTypes);
    }
}
