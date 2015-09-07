<?hh
namespace Elastica;

use Elastica\Bulk\Action;
use Elastica\Exception\InvalidException;
use Elastica\Exception\NotImplementedException;
use Indexish;

/**
 * Single document stored in elastic search.
 *
 * @author   Nicolas Ruflin <spam@ruflin.com>
 */
class Document extends AbstractUpdateAction
{
    const OP_TYPE_CREATE = Action::OP_TYPE_CREATE;

    /**
     * Document data.
     *
     * @var array Document data
     */
    protected ?Indexish<string, mixed> $_data = null;

    /**
     * @var string Serialized document data
     */
    protected ?string $_serialized = null;

    /**
     * Whether to use this document to upsert if the document does not exist.
     *
     * @var bool
     */
    protected bool $_docAsUpsert = false;

    /**
     * @var bool
     */
    protected bool $_autoPopulate = false;

    /**
     * Creates a new document.
     *
     * @param string       $id    OPTIONAL $id Id is create if empty
     * @param array|string $data  OPTIONAL Data array
     * @param string|Type  $type  OPTIONAL Type name
     * @param string|Index $index OPTIONAL Index name
     */
    public function __construct(?string $id = '', mixed $data = array(), mixed $type = '', mixed $index = '')
    {
        $this->setId($id);
        $this->setData($data);
        $this->setType($type);
        $this->setIndex($index);
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key) : mixed
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function __set(string $key, mixed $value) : void
    {
        $this->set($key, $value);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function __isset(string $key) : bool
    {
        return $this->has($key) && null !== $this->get($key);
    }

    /**
     * @param string $key
     */
    public function __unset(string $key) : void
    {
        $this->remove($key);
    }

    /**
     * @param string $key
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return mixed
     */
    public function get(string $key) : mixed
    {
        if (!$this->has($key)) {
            throw new InvalidException("Field {$key} does not exist");
        }

        return /* UNSAFE_EXPR */ $this->_data[$key];
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return $this
     */
    public function set(string $key, mixed $value) : this
    {
        if ($this->_data === null) {
            throw new InvalidException('Document data is serialized data. Data creation is forbidden.');
        }
        $this->_data[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key) : bool
    {
        return $this->_data instanceof Indexish && array_key_exists($key, $this->_data);
    }

    /**
     * @param string $key
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return $this
     */
    public function remove(string $key) : this
    {
        if (!$this->has($key)) {
            throw new InvalidException("Field {$key} does not exist");
        }
        unset(/* UNSAFE_EXPR */ $this->_data[$key]);

        return $this;
    }

    /**
     * Adds the given key/value pair to the document.
     *
     * @deprecated
     *
     * @param string $key   Document entry key
     * @param mixed  $value Document entry value
     *
     * @return $this
     */
    public function add(string $key, mixed $value) : this
    {
        return $this->set($key, $value);
    }

    /**
     * Adds a file to the index.
     *
     * To use this feature you have to call the following command in the
     * elasticsearch directory:
     * <code>
     * ./bin/plugin -install elasticsearch/elasticsearch-mapper-attachments/1.6.0
     * </code>
     * This installs the tika file analysis plugin. More infos about supported formats
     * can be found here: {@link http://tika.apache.org/0.7/formats.html}
     *
     * @param string $key      Key to add the file to
     * @param string $filepath Path to add the file
     * @param string $mimeType OPTIONAL Header mime type
     *
     * @return $this
     */
    public function addFile(string $key, string $filepath, string $mimeType = '') : this
    {
        $value = base64_encode(file_get_contents($filepath));

        if (!empty($mimeType)) {
            $value = array('_content_type' => $mimeType, '_name' => $filepath, '_content' => $value);
        }

        $this->set($key, $value);

        return $this;
    }

    /**
     * Add file content.
     *
     * @param string $key     Document key
     * @param string $content Raw file content
     *
     * @return $this
     */
    public function addFileContent(string $key, string $content) : this
    {
        return $this->set($key, base64_encode($content));
    }

    /**
     * Adds a geopoint to the document.
     *
     * Geohashes are not yet supported
     *
     * @param string $key       Field key
     * @param float  $latitude  Latitude value
     * @param float  $longitude Longitude value
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-geo-point-type.html
     *
     * @return $this
     */
    public function addGeoPoint(string $key, float $latitude, float $longitude) : this
    {
        $value = array('lat' => $latitude, 'lon' => $longitude);

        $this->set($key, $value);

        return $this;
    }

    /**
     * Overwrites the current document data with the given data.
     *
     * @param array $data Data array
     *
     * @return $this
     */
    public function setData(mixed $data) : this
    {
        if ($data instanceof Indexish) {
            $this->_data = $data;
            $this->_serialized = null;
        } elseif (is_string($data)) {
            $this->_data = null;
            $this->_serialized = $data;
        }

        return $this;
    }

    /**
     * Returns the document data.
     *
     * @return array|string Document data
     */
    public function getData() : mixed
    {
        if ($this->_data !== null) {
            return $this->_data;
        } elseif ($this->_serialized !== null) {
            return $this->_serialized;
        }
        throw new \RuntimeException('no data exists');
    }

    /**
     * @deprecated
     *
     * @param \Elastica\Script $data
     *
     * @throws NotImplementedException
     */
    public function setScript(Script $data) : void
    {
        throw new NotImplementedException('setScript() is no longer available as of 0.90.2. See http://elastica.io/migration/0.90.2/upsert.html to migrate');
    }

    /**
     * @throws NotImplementedException
     *
     * @deprecated
     */
    public function getScript() : void
    {
        throw new NotImplementedException('getScript() is no longer available as of 0.90.2. See http://elastica.io/migration/0.90.2/upsert.html to migrate');
    }

    /**
     * @throws NotImplementedException
     *
     * @deprecated
     */
    public function hasScript() : void
    {
        throw new NotImplementedException('hasScript() is no longer available as of 0.90.2. See http://elastica.io/migration/0.90.2/upsert.html to migrate');
    }

    /**
     * @param bool $value
     *
     * @return $this
     */
    public function setDocAsUpsert(bool $value) : this
    {
        $this->_docAsUpsert = (bool) $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function getDocAsUpsert() : bool
    {
        return $this->_docAsUpsert;
    }

    /**
     * @param bool $autoPopulate
     *
     * @return $this
     */
    public function setAutoPopulate(bool $autoPopulate = true) : this
    {
        $this->_autoPopulate = (bool) $autoPopulate;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoPopulate() : bool
    {
        return $this->_autoPopulate;
    }

    /**
     * Returns the document as an array.
     *
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $doc = $this->getParams();
        $doc['_source'] = $this->getData();

        return $doc;
    }

    /**
     * @param array|\Elastica\Document $data
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return self
     */
    public static function create(mixed $data) : Document
    {
        if ($data instanceof self) {
            return $data;
        } elseif ($data instanceof Indexish) {
            return new self('', $data);
        } else {
            throw new InvalidException('Failed to create document. Invalid data passed.');
        }
    }
}
