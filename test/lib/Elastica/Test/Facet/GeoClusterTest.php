<?hh
namespace Elastica\Test\Facet;

use Elastica\Document;
use Elastica\Facet\GeoCluster;
use Elastica\Query;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;

class GeoClusterTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testQuery() : void
    {
        $client = $this->_getClient();
        $nodes = $client->getCluster()->getWaitHandle()->join()->getNodes();
        if (!$nodes[0]->getInfo()->getWaitHandle()->join()->hasPlugin('geocluster-facet')->getWaitHandle()->join()) {
            $this->markTestSkipped('geocluster-facet plugin not installed');
        }

        $index = $this->_createIndex();
        $type = $index->getType('testQuery');
        $geoField = 'location';

        $type->setMapping(new Mapping($type, array(
            $geoField => array('type' => 'geo_point', 'lat_lon' => true),
        )))->getWaitHandle()->join();

        $doc = new Document('1', array('name' => 'item1', 'location' => array(20, 20)));
        $type->addDocument($doc)->getWaitHandle()->join();

        $doc = new Document('2', array('name' => 'item2', 'location' => array(20, 20)));
        $type->addDocument($doc)->getWaitHandle()->join();

        $doc = new Document('3', array('name' => 'item3', 'location' => array(20, 20)));
        $type->addDocument($doc)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $facet = new GeoCluster('clusters');
        $facet
            ->setField($geoField)
            ->setFactor(1.0)
            ->setShowIds(false);
        $query = new Query();
        $query->setFacets(array($facet));

        $response = $type->search($query)->getWaitHandle()->join();
        $facets = $response->getFacets();

        $this->assertEquals(1, count($facets['clusters']['clusters']));

        $index->delete()->getWaitHandle()->join();
    }
}
