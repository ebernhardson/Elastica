<?hh
namespace Elastica\Multi;

use Elastica\Client;
use Elastica\JSON;
use Elastica\Request;
use Elastica\Search as BaseSearch;

/**
 * Elastica multi search.
 *
 * @author munkie
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-multi-search.html
 */
class Search
{
    /**
     * @var array|\Elastica\Search[]
     */
    protected array $_searches = array();

    /**
     * @var array
     */
    protected array $_options = array();

    /**
     * @var \Elastica\Client
     */
    protected Client $_client;

    /**
     * Constructs search object.
     *
     * @param \Elastica\Client $client Client object
     */
    public function __construct(Client $client)
    {
        $this->_client = $client;
    }

    /**
     * @return \Elastica\Client
     */
    public function getClient() : Client
    {
        return $this->_client;
    }

    /**
     * @param \Elastica\Client $client
     *
     * @return $this
     */
    public function setClient(Client $client) : Search
    {
        $this->_client = $client;

        return $this;
    }

    /**
     * @return $this
     */
    public function clearSearches() : Search
    {
        $this->_searches = array();

        return $this;
    }

    /**
     * @param \Elastica\Search $search
     * @param string           $key    Optional key
     *
     * @return $this
     */
    public function addSearch(BaseSearch $search, ?string $key = null) : Search
    {
        if ($key !== null && $key) {
            $this->_searches[$key] = $search;
        } else {
            $this->_searches[] = $search;
        }

        return $this;
    }

    /**
     * @param array|\Elastica\Search[] $searches
     *
     * @return $this
     */
    public function addSearches(array $searches) : Search
    {
        foreach ($searches as $key => $search) {
            $this->addSearch($search, (string)$key);
        }

        return $this;
    }

    /**
     * @param array|\Elastica\Search[] $searches
     *
     * @return $this
     */
    public function setSearches(array $searches) : Search
    {
        $this->clearSearches();
        $this->addSearches($searches);

        return $this;
    }

    /**
     * @return array|\Elastica\Search[]
     */
    public function getSearches() : array
    {
        return $this->_searches;
    }

    /**
     * @param string $searchType
     *
     * @return $this
     */
    public function setSearchType(string $searchType) : Search
    {
        $this->_options[BaseSearch::OPTION_SEARCH_TYPE] = $searchType;

        return $this;
    }

    /**
     * @return Awaitable<\Elastica\Multi\ResultSet>
     */
    public async function search() : Awaitable<ResultSet>
    {
        $data = $this->_getData();

        $response = await $this->getClient()->request(
            '_msearch',
            Request::POST,
            $data,
            $this->_options
        );

        return new ResultSet($response, $this->getSearches());
    }

    /**
     * @return string
     */
    protected function _getData() : string
    {
        $data = '';
        foreach ($this->getSearches() as $search) {
            $data .= $this->_getSearchData($search);
        }

        return $data;
    }

    /**
     * @param \Elastica\Search $search
     *
     * @return string
     */
    protected function _getSearchData(BaseSearch $search) : string
    {
        $header = $this->_getSearchDataHeader($search);
        $header = (empty($header)) ? new \stdClass() : $header;
        $query = $search->getQuery();

        $data = JSON::stringify($header)."\n";
        $data .= JSON::stringify($query->toArray())."\n";

        return $data;
    }

    /**
     * @param \Elastica\Search $search
     *
     * @return array
     */
    protected function _getSearchDataHeader(BaseSearch $search) : array
    {
        $header = $search->getOptions();

        if ($search->hasIndices()) {
            $header['index'] = $search->getIndices();
        }

        if ($search->hasTypes()) {
            $header['types'] = $search->getTypes();
        }

        return $header;
    }
}
