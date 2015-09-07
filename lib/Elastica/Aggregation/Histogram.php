<?hh // strict
namespace Elastica\Aggregation;

/**
 * Class Histogram.
 *
 * @link http://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-bucket-histogram-aggregation.html
 */
class Histogram extends AbstractSimpleAggregation
{
    /**
     * @param string $name     the name of this aggregation
     * @param string $field    the name of the field on which to perform the aggregation
     * @param string $interval the interval by which documents will be bucketed
     */
    public function __construct(string $name, string $field, string $interval)
    {
        parent::__construct($name);
        $this->setField($field);
        $this->setInterval($interval);
    }

    /**
     * Set the interval by which documents will be bucketed. In elasticsearch
     * interval format.
     *
     * @param string $interval
     *
     * @return $this
     */
    public function setInterval(string $interval) : this
    {
        return $this->setParam('interval', $interval);
    }

    /**
     * Set the bucket sort order.
     *
     * @param string $order     "_count", "_term", or the name of a sub-aggregation or sub-aggregation response field
     * @param string $direction "asc" or "desc"
     *
     * @return $this
     */
    public function setOrder(string $order, string $direction) : this
    {
        return $this->setParam('order', array($order => $direction));
    }

    /**
     * Set the minimum number of documents which must fall into a bucket in order for the bucket to be returned.
     *
     * @param int $count set to 0 to include empty buckets
     *
     * @return $this
     */
    public function setMinimumDocumentCount(int $count) : this
    {
        return $this->setParam('min_doc_count', $count);
    }
}
