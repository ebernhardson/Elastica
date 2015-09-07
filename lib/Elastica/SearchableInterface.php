<?hh
namespace Elastica;

/**
 * Elastica searchable interface.
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 */
interface SearchableInterface
{
    /**
     * Searches results for a query.
     *
     * TODO: Improve sample code
     * {
     *     "from" : 0,
     *     "size" : 10,
     *     "sort" : {
     *          "postDate" : {"reverse" : true},
     *          "user" : { },
     *          "_score" : { }
     *      },
     *      "query" : {
     *          "term" : { "user" : "kimchy" }
     *      }
     * }
     *
     * @param string|array|\Elastica\Query $query Array with all query data inside or a Elastica\Query object
     *
     * @return \Elastica\ResultSet ResultSet with all results inside
     */
    public function search(mixed $query = '', mixed $options = null) : Awaitable<ResultSet>;

    /**
     * Counts results for a query.
     *
     * If no query is set, matchall query is created
     *
     * @param string|array|\Elastica\Query $query Array with all query data inside or a Elastica\Query object
     *
     * @return int number of documents matching the query
     */
    public function count(mixed $query = '') : Awaitable<int>;

    /**
     * @param string|array|\Elastica\Query $query
     * @param array|int                    $options
     *
     * @return \Elastica\Search
     */
    public function createSearch(mixed $query = '', mixed $options = null) : Search;
}
