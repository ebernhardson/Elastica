<?hh // strict
namespace Elastica\Bulk\Action;

use Elastica\AbstractUpdateAction;
use Elastica\Bulk\Action;
use Elastica\Document;
use Elastica\Script;
use Indexish;

abstract class AbstractDocument extends Action
{
    /**
     * @var \Elastica\Document|\Elastica\Script
     */
    protected mixed $_data;

    /**
     * @param \Elastica\Document|\Elastica\Script $document
     */
    public function __construct(mixed $document)
    {
        parent::__construct($this->_opType);
        $this->setData($document);
    }

    /**
     * @param \Elastica\Document $document
     *
     * @return $this
     */
    public function setDocument(Document $document) : this
    {
        $this->_data = $document;

        $metadata = $this->_getMetadata($document);

        $this->setMetadata($metadata);

        return $this;
    }

    /**
     * @param \Elastica\Script $script
     *
     * @return $this
     */
    public function setScript(Script $script) : this
    {
        if (!($this instanceof UpdateDocument)) {
            throw new \BadMethodCallException('setScript() can only be used for UpdateDocument');
        }

        $this->_data = $script;

        $metadata = $this->_getMetadata($script);
        $this->setMetadata($metadata);

        return $this;
    }

    /**
     * @param \Elastica\Script|\Elastica\Document $data
     *
     * @throws \InvalidArgumentException
     *
     * @return $this
     */
    public function setData(mixed $data) : this
    {
        if ($data instanceof Script) {
            $this->setScript($data);
        } elseif ($data instanceof Document) {
            $this->setDocument($data);
        } else {
            throw new \InvalidArgumentException('Data should be a Document or a Script.');
        }

        return $this;
    }

    /**
     * Note: This is for backwards compatibility.
     *
     * @return \Elastica\Document|null
     */
    public function getDocument() : ?Document
    {
        if ($this->_data instanceof Document) {
            return $this->_data;
        }

        return null;
    }

    /**
     * Note: This is for backwards compatibility.
     *
     * @return \Elastica\Script|null
     */
    public function getScript() : ?Script
    {
        if ($this->_data instanceof Script) {
            return $this->_data;
        }

        return null;
    }

    /**
     * @return \Elastica\Document|\Elastica\Script
     */
    public function getData() : mixed
    {
        return $this->_data;
    }

    /**
     * @param \Elastica\AbstractUpdateAction $source
     *
     * @return array
     */
    abstract protected function _getMetadata(AbstractUpdateAction $source) : Indexish<string, mixed>; 

    /**
     * @param \Elastica\Document|\Elastica\Script $data
     * @param string                              $opType
     *
     * @return static
     */
    public static function create(AbstractUpdateAction $data, ?string $opType = null) : AbstractDocument
    {
        //Check type
        if (!($data instanceof Document) && !($data instanceof Script)) {
            throw new \InvalidArgumentException('The data needs to be a Document or a Script.');
        }

        if (null === $opType && $data->hasOpType()) {
            $opType = $data->getOpType();
        }

        //Check that scripts can only be used for updates
        if ($data instanceof Script) {
            if ($opType === null) {
                $opType = self::OP_TYPE_UPDATE;
            } elseif ($opType != self::OP_TYPE_UPDATE) {
                throw new \InvalidArgumentException('Scripts can only be used with the update operation type.');
            }
        }

        if ($opType === null) {
            $opType = self::OP_TYPE_INDEX;
        }
        switch ($opType) {
            case self::OP_TYPE_DELETE:
                $action = new DeleteDocument($data);
                break;
            case self::OP_TYPE_CREATE:
                $action = new CreateDocument($data);
                break;
            case self::OP_TYPE_UPDATE:
                $action = new UpdateDocument($data);
                break;
            case self::OP_TYPE_INDEX:
            default:
                $action = new IndexDocument($data);
                break;
        }

        return $action;
    }
}
