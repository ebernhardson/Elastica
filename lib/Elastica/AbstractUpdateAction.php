<?hh
namespace Elastica;

use Indexish;

/**
 * Base class for things that can be sent to the update api (Document and
 * Script).
 *
 * @author   Nik Everett <nik9000@gmail.com>
 */
class AbstractUpdateAction extends Param
{
    /**
     * @var \Elastica\Document
     */
    protected ?Document $_upsert;

    /**
     * Sets the id of the document.
     *
     * @param string $id
     *
     * @return $this
     */
    public function setId(?string $id) : this
    {
        return $this->setParam('_id', $id);
    }

    /**
     * Returns document id.
     *
     * @return string|null Document id
     */
    public function getId() : ?string
    {
        return ($this->hasParam('_id')) ? (string)$this->getParam('_id') : null;
    }

    /**
     * @return bool
     */
    public function hasId() : bool
    {
        return '' !== (string) $this->getId();
    }

    /**
     * Sets lifetime of document.
     *
     * @param string $ttl
     *
     * @return $this
     */
    public function setTtl(string $ttl) : this
    {
        return $this->setParam('_ttl', $ttl);
    }

    /**
     * @return string
     */
    public function getTtl() : string
    {
        return (string)$this->getParam('_ttl');
    }

    /**
     * @return bool
     */
    public function hasTtl() : bool
    {
        return $this->hasParam('_ttl');
    }

    /**
     * Sets the document type name.
     *
     * @param \Elastica\Type|string $type Type name
     *
     * @return $this
     */
    public function setType(mixed $type) : this
    {
        if ($type instanceof Type) {
            $this->setIndex($type->getIndex());
            $type = $type->getName();
        }

        return $this->setParam('_type', $type);
    }

    /**
     * Return document type name.
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return string Document type name
     */
    public function getType() : string
    {
        return (string)$this->getParam('_type');
    }

    /**
     * Sets the document index name.
     *
     * @param Index|string $index Index name
     *
     * @return $this
     */
    public function setIndex(mixed $index) : this
    {
        if ($index instanceof Index) {
            $index = $index->getName();
        }

        return $this->setParam('_index', (string)$index);
    }

    /**
     * Get the document index name.
     *
     * @throws \Elastica\Exception\InvalidException
     *
     * @return string Index name
     */
    public function getIndex() : string
    {
        return (string)$this->getParam('_index');
    }

    /**
     * Sets the version of a document for use with optimistic concurrency control.
     *
     * @param int $version Document version
     *
     * @return $this
     *
     * @link https://www.elastic.co/blog/versioning
     */
    public function setVersion(int $version) : this
    {
        return $this->setParam('_version', (int) $version);
    }

    /**
     * Returns document version.
     *
     * @return string Document version
     */
    public function getVersion() : mixed
    {
        return (string)$this->getParam('_version');
    }

    /**
     * @return bool
     */
    public function hasVersion() : bool
    {
        return $this->hasParam('_version');
    }

    /**
     * Sets the version_type of a document
     * Default in ES is internal, but you can set to external to use custom versioning.
     *
     * @param int $versionType Document version type
     *
     * @return $this
     */
    public function setVersionType(int $versionType) : this
    {
        return $this->setParam('_version_type', $versionType);
    }

    /**
     * Returns document version type.
     *
     * @return string Document version type
     */
    public function getVersionType() : mixed
    {
        return (string)$this->getParam('_version_type');
    }

    /**
     * @return bool
     */
    public function hasVersionType() : bool
    {
        return $this->hasParam('_version_type');
    }

    /**
     * Sets parent document id.
     *
     * @param string $parent Parent document id
     *
     * @return $this
     *
     * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-parent-field.html
     */
    public function setParent(mixed $parent) : this
    {
        return $this->setParam('_parent', (string)$parent);
    }

    /**
     * Returns the parent document id.
     *
     * @return string Parent document id
     */
    public function getParent() : string
    {
        return (string)$this->getParam('_parent');
    }

    /**
     * @return bool
     */
    public function hasParent() : bool
    {
        return $this->hasParam('_parent');
    }

    /**
     * Set operation type.
     *
     * @param string $opType Only accept create
     *
     * @return $this
     */
    public function setOpType(string $opType) : this
    {
        return $this->setParam('_op_type', $opType);
    }

    /**
     * Get operation type.
     *
     * @return string
     */
    public function getOpType() : string
    {
        return (string)$this->getParam('_op_type');
    }

    /**
     * @return bool
     */
    public function hasOpType() : bool
    {
        return $this->hasParam('_op_type');
    }

    /**
     * Set percolate query param.
     *
     * @param string $value percolator filter
     *
     * @return $this
     */
    public function setPercolate(string $value = '*') : this
    {
        return $this->setParam('_percolate', $value);
    }

    /**
     * Get percolate parameter.
     *
     * @return string
     */
    public function getPercolate() : string
    {
        return (string)$this->getParam('_percolate');
    }

    /**
     * @return bool
     */
    public function hasPercolate() : bool
    {
        return $this->hasParam('_percolate');
    }

    /**
     * Set routing query param.
     *
     * @param string $value routing
     *
     * @return $this
     */
    public function setRouting(string $value) : this
    {
        return $this->setParam('_routing', $value);
    }

    /**
     * Get routing parameter.
     *
     * @return string
     */
    public function getRouting() : string
    {
        return (string)$this->getParam('_routing');
    }

    /**
     * @return bool
     */
    public function hasRouting() : bool
    {
        return $this->hasParam('_routing');
    }

    /**
     * @param array|string $fields
     *
     * @return $this
     */
    public function setFields(mixed $fields) : this
    {
        if ($fields instanceof Indexish) {
            $fields = implode(',', $fields);
        }

        return $this->setParam('_fields', (string) $fields);
    }

    /**
     * @return $this
     */
    public function setFieldsSource() : this
    {
        return $this->setFields('_source');
    }

    /**
     * @return string
     */
    public function getFields() : string
    {
        return (string)$this->getParam('_fields');
    }

    /**
     * @return bool
     */
    public function hasFields() : bool
    {
        return $this->hasParam('_fields');
    }

    /**
     * @param int $num
     *
     * @return $this
     */
    public function setRetryOnConflict(int $num) : this
    {
        return $this->setParam('_retry_on_conflict', (int) $num);
    }

    /**
     * @return int
     */
    public function getRetryOnConflict() : int
    {
        return (int)$this->getParam('_retry_on_conflict');
    }

    /**
     * @return bool
     */
    public function hasRetryOnConflict() : bool
    {
        return $this->hasParam('_retry_on_conflict');
    }

    /**
     * @param string $timestamp
     *
     * @return $this
     */
    public function setTimestamp(string $timestamp) : this
    {
        return $this->setParam('_timestamp', $timestamp);
    }

    /**
     * @return int
     */
    public function getTimestamp() : int
    {
        return (int)$this->getParam('_timestamp');
    }

    /**
     * @return bool
     */
    public function hasTimestamp() : bool
    {
        return $this->hasParam('_timestamp');
    }

    /**
     * @param bool $refresh
     *
     * @return $this
     */
    public function setRefresh(bool $refresh = true) : this
    {
        return $this->setParam('_refresh', (bool) $refresh);
    }

    /**
     * @return bool
     */
    public function getRefresh() : bool
    {
        return (bool)$this->getParam('_refresh');
    }

    /**
     * @return bool
     */
    public function hasRefresh() : bool
    {
        return $this->hasParam('_refresh');
    }

    /**
     * @param string $timeout
     *
     * @return $this
     */
    public function setTimeout(string $timeout) : this
    {
        return $this->setParam('_timeout', $timeout);
    }

    /**
     * @return bool
     */
    public function getTimeout() : bool
    {
        return (bool)$this->getParam('_timeout');
    }

    /**
     * @return bool
     */
    public function hasTimeout() : bool
    {
        return $this->hasParam('_timeout');
    }

    /**
     * @param string $timeout
     *
     * @return $this
     */
    public function setConsistency(string $timeout) : this
    {
        return $this->setParam('_consistency', $timeout);
    }

    /**
     * @return string
     */
    public function getConsistency() : string
    {
        return (string)$this->getParam('_consistency');
    }

    /**
     * @return bool
     */
    public function hasConsistency() : bool
    {
        return $this->hasParam('_consistency');
    }

    /**
     * @param string $timeout
     *
     * @return $this
     */
    public function setReplication(string $timeout) : this
    {
        return $this->setParam('_replication', $timeout);
    }

    /**
     * @return string
     */
    public function getReplication() : string
    {
        return (string)$this->getParam('_replication');
    }

    /**
     * @return bool
     */
    public function hasReplication() : bool
    {
        return $this->hasParam('_replication');
    }

    /**
     * @param \Elastica\Document|array $data
     *
     * @return $this
     */
    public function setUpsert(mixed $data) : this
    {
        $document = Document::create($data);
        $this->_upsert = $document;

        return $this;
    }

    /**
     * @return \Elastica\Document
     */
    public function getUpsert() : ?Document
    {
        return $this->_upsert;
    }

    /**
     * @return bool
     */
    public function hasUpsert() : bool
    {
        return null !== $this->_upsert;
    }

    /**
     * @param array $fields         if empty array all options will be returned, field names can be either with underscored either without, i.e. _percolate, routing
     * @param bool  $withUnderscore should option keys contain underscore prefix
     *
     * @return array
     */
    public function getOptions(array $fields = array(), bool $withUnderscore = false) : Indexish<string, mixed>
    {
        if (!empty($fields)) {
            $data = Map {};
            foreach ($fields as $field) {
                $key = '_'.ltrim($field, '_');
                if ($this->hasParam($key) && '' !== (string) $this->getParam($key)) {
                    $data[$key] = $this->getParam($key);
                }
            }
        } else {
            $data = $this->getParams();
        }
        if (!$withUnderscore) {
            foreach ($data->keys() as $key) {
                $data[ltrim($key, '_')] = $data[$key];
                unset($data[$key]);
            }
        }

        return $data;
    }
}
