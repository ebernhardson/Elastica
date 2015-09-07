<?hh
namespace Elastica\Query;

/**
 * Image query.
 *
 * @author   Jacques Moati <jacques@moati.net>
 *
 * @link     https://github.com/kzwang/elasticsearch-image
 *
 * To use this feature you have to call the following command in the
 * elasticsearch directory:
 * <code>
 * ./bin/plugin --url https://github.com/Jmoati/elasticsearch-image/releases/download/1.7.1/elasticsearch-image-1.7.1.zip --install image
 * </code>
 * This installs the image plugin. More infos
 * can be found here: {@link https://github.com/Jmoati/elasticsearch-image}
 */
class Image extends AbstractQuery
{
    public function __construct(Map<string, mixed> $image = Map {})
    {
        $this->setParams($image);
    }

    /**
     * Sets a param for the given field.
     *
     * @param string $field
     * @param string $key
     * @param string|float $value
     *
     * @return $this
     */
    public function setFieldParam(string $field, string $key, mixed $value) : this
    {
        if (!isset($this->_params[$field])) {
            $this->_params[$field] = array();
        }

        /* UNSAFE_EXPR */
        $this->_params[$field][$key] = $value;

        return $this;
    }

    /**
     * Set field boost value.
     *
     * If not set, defaults to 1.0.
     *
     * @param string $field
     * @param float  $boost
     *
     * @return $this
     */
    public function setFieldBoost(string $field, float $boost = 1.0) : this
    {
        return $this->setFieldParam($field, 'boost', $boost);
    }

    /**
     * Set field feature value.
     *
     * If not set, defaults CEDD.
     *
     * @param string $field
     * @param string $feature
     *
     * @return $this
     */
    public function setFieldFeature(string $field, string $feature = 'CEDD') : this
    {
        return $this->setFieldParam($field, 'feature', $feature);
    }

    /**
     * Set field hash value.
     *
     * If not set, defaults BIT_SAMPLING.
     *
     * @param string $field
     * @param string $hash
     *
     * @return $this
     */
    public function setFieldHash(string $field, string $hash = 'BIT_SAMPLING') : this
    {
        return $this->setFieldParam($field, 'hash', $hash);
    }

    /**
     * Set field image value.
     *
     * @param string $field
     * @param string $path  File will be base64_encode
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function setFieldImage(string $field, string $path) : this
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new \Exception(sprintf("File %s can't be open", $path));
        }

        return $this->setFieldParam($field, 'image', base64_encode(file_get_contents($path)));
    }

    /**
     * Set field index value.
     *
     * @param string $field
     * @param string $index
     *
     * @return $this
     */
    public function setFieldIndex(string $field, string $index) : this
    {
        return $this->setFieldParam($field, 'index', $index);
    }

    /**
     * Set field type value.
     *
     * @param string $field
     * @param string $type
     *
     * @return $this
     */
    public function setFieldType(string $field, string $type) : this
    {
        return $this->setFieldParam($field, 'type', $type);
    }

    /**
     * Set field id value.
     *
     * @param string $field
     * @param string $id
     *
     * @return $this
     */
    public function setFieldId(string $field, string $id) : this
    {
        return $this->setFieldParam($field, 'id', $id);
    }

    /**
     * Set field path value.
     *
     * @param string $field
     * @param string $path
     *
     * @return $this
     */
    public function setFieldPath(string $field, string $path) : this
    {
        return $this->setFieldParam($field, 'path', $path);
    }

    /**
     * Define quickly a reference image already in your elasticsearch database.
     *
     * If not set, path will be the same as $field.
     *
     * @param string $field
     * @param string $index
     * @param string $type
     * @param string $id
     * @param string $path
     *
     * @return $this
     */
    public function setImageByReference(string $field, string $index, string $type, string $id, ?string $path = null) : this
    {
        if (null === $path) {
            $path = $field;
        }

        $this->setFieldIndex($field, $index);
        $this->setFieldType($field, $type);
        $this->setFieldId($field, $id);

        return $this->setFieldPath($field, $path);
    }
}
