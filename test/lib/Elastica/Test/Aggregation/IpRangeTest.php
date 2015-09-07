<?hh
namespace Elastica\Test\Aggregation;

use Elastica\Aggregation\IpRange;
use Elastica\Document;
use Elastica\Query;
use Elastica\Type\Mapping;

class IpRangeTest extends BaseAggregationTest
{
    protected function _getIndexForTest() : \Elastica\Index
    {
        $index = $this->_createIndex();
        $type = $index->getType('test');

        $type->setMapping(new Mapping(null, array(
            'address' => array('type' => 'ip'),
        )))->getWaitHandle()->join();

        $type->addDocuments(array(
            new Document('1', array('address' => '192.168.1.100')),
            new Document('2', array('address' => '192.168.1.150')),
            new Document('3', array('address' => '192.168.1.200')),
        ))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }

    /**
     * @group functional
     */
    public function testIpRangeAggregation() : void
    {
        $agg = new IpRange('ip', 'address');
        $agg->addRange('192.168.1.101');
        $agg->addRange(null, '192.168.1.200');

        $cidrRange = '192.168.1.0/24';
        $agg->addMaskRange($cidrRange);

        $query = new Query();
        $query->addAggregation($agg);
        $response = $this->_getIndexForTest()->search($query)->getWaitHandle()->join();
        $results = $response->getAggregation('ip');

        foreach ($results['buckets'] as $bucket) {
            if (array_key_exists('key', $bucket) && $bucket['key'] == $cidrRange) {
                // the CIDR mask
                $this->assertEquals(3, $bucket['doc_count']);
            } else {
                // the normal ip ranges
                $this->assertEquals(2, $bucket['doc_count']);
            }
        }
    }
}
