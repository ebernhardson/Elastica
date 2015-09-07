<?hh
namespace Elastica\Suggest;

use Elastica\Exception\InvalidException;
use Elastica\NameableInterface;
use Elastica\Param;
use Indexish;

/**
 * Class AbstractSuggestion.
 */
abstract class AbstractSuggest extends Param implements NameableInterface
{
    /**
     * @var string the name of this suggestion
     */
    protected string $_name;

    /**
     * @var string the text for this suggestion
     */
    protected ?string $_text;

    /**
     * @param string $name
     * @param string $field
     */
    public function __construct(string $name, string $field)
    {
        $this->_name = $name;
        $this->setField($field);
    }

    /**
     * Suggest text must be set either globally or per suggestion.
     *
     * @param string $text
     *
     * @return $this
     */
    public function setText(string $text) : this
    {
        $this->_text = $text;

        return $this;
    }

    /**
     * @param string $field
     *
     * @return $this
     */
    public function setField(string $field) : this
    {
        return $this->setParam('field', $field);
    }

    /**
     * @param int $size
     *
     * @return $this
     */
    public function setSize(int $size) : this
    {
        return $this->setParam('size', $size);
    }

    /**
     * @param int $size maximum number of suggestions to be retrieved from each shard
     *
     * @return $this
     */
    public function setShardSize(int $size) : this
    {
        return $this->setParam('shard_size', $size);
    }

    /**
     * Sets the name of the suggest. It is automatically set by
     * the constructor.
     *
     * @param string $name The name of the facet.
     *
     * @throws \Elastica\Exception\InvalidException If name is empty
     *
     * @return $this
     */
    public function setName(string $name) : this
    {
        if (empty($name)) {
            throw new InvalidException('Suggest name has to be set');
        }
        $this->_name = $name;

        return $this;
    }

    /**
     * Retrieve the name of this suggestion.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->_name;
    }

    /**
     * @return array
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();
        if (isset($this->_text)) {
            $array['text'] = $this->_text;
        }

        return $array;
    }
}
