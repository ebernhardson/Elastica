<?hh
namespace Elastica;

use Elastica\Exception\NotImplementedException;
use Elastica\Suggest\AbstractSuggest;
use Indexish;

/**
 * Class Suggest.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters.html
 */
class Suggest extends Param
{
    /**
     * @param AbstractSuggest $suggestion
     */
    public function __construct(?AbstractSuggest $suggestion = null)
    {
        if (!is_null($suggestion)) {
            $this->addSuggestion($suggestion);
        }
    }

    /**
     * Set the global text for this suggester.
     *
     * @param string $text
     *
     * @return $this
     */
    public function setGlobalText(string $text) : this
    {
        return $this->setParam('text', $text);
    }

    /**
     * Add a suggestion to this suggest clause.
     *
     * @param AbstractSuggest $suggestion
     *
     * @return $this
     */
    public function addSuggestion(AbstractSuggest $suggestion) : this
    {
        return $this->addParam('suggestion', $suggestion);
    }

    /**
     * @param Suggest|AbstractSuggest $suggestion
     *
     * @throws Exception\NotImplementedException
     *
     * @return self
     */
    public static function create(mixed $suggestion) : Suggest
    {
        if ($suggestion instanceof self) {
            return $suggestion;
        } elseif ($suggestion instanceof AbstractSuggest) {
            return new self($suggestion);
        }
        throw new NotImplementedException();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() : Indexish<string, mixed>
    {
        $array = parent::toArray();

        $baseName = $this->_getBaseName();

        if (isset(/* UNSAFE_EXPR */ $array[$baseName]['suggestion'])) {
            $suggestion = /* UNSAFE_EXPR */ $array[$baseName]['suggestion'];
            unset(/* UNSAFE_EXPR */ $array[$baseName]['suggestion']);

            foreach ($suggestion as $key => $value) {
                /* UNSAFE_EXPR */
                $array[$baseName][$key] = $value;
            }
        }

        return $array;
    }
}
