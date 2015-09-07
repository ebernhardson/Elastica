<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\GeoDistance;
use Elastica\Query;
use Elastica\Query\MatchAll;
use Elastica\Test\Base as BaseTest;

class GeoDistanceTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGeoPoint() : void
    {
        $index = $this->_createIndex();

        $type = $index->getType('test');

        // Set mapping
        $type->setMapping(array('point' => array('type' => 'geo_point')))->getWaitHandle()->join();

        // Add doc 1
        $doc1 = new Document('1',
            array(
                'name' => 'ruflin',
            )
        );

        $doc1->addGeoPoint('point', 17.0, 19.0);
        $type->addDocument($doc1)->getWaitHandle()->join();

        // Add doc 2
        $doc2 = new Document('2',
            array(
                'name' => 'ruflin',
            )
        );

        $doc2->addGeoPoint('point', 30.0, 40.0);
        $type->addDocument($doc2)->getWaitHandle()->join();

        $index->optimize()->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        // Only one point should be in radius
        $query = new Query();
        $geoFilter = new GeoDistance('point', array('lat' => 30.0, 'lon' => 40.0), '1km');

        $query = new Query(new MatchAll());
        $query->setPostFilter($geoFilter);
        $this->assertEquals(1, $type->search($query)->getWaitHandle()->join()->count());

        // Both points should be inside
        $query = new Query();
        $geoFilter = new GeoDistance('point', array('lat' => 30.0, 'lon' => 40.0), '40000km');
        $query = new Query(new MatchAll());
        $query->setPostFilter($geoFilter);
        $index->refresh()->getWaitHandle()->join();

        $this->assertEquals(2, $type->search($query)->getWaitHandle()->join()->count());
    }

    /**
     * @group unit
     */
    public function testConstructLatlon() : void
    {
        $key = 'location';
        $location = array(
            'lat' => 48.86,
            'lon' => 2.35,
        );
        $distance = '10km';

        $filter = new GeoDistance($key, $location, $distance);

        $expected = array(
            'geo_distance' => Map {
                $key => $location,
                'distance' => $distance,
            },
        );

        $data = $filter->toArray();

        $this->assertEquals($expected, $data);
    }

    /**
     * @group unit
     */
    public function testConstructGeohash() : void
    {
        $key = 'location';
        $location = 'u09tvqx';
        $distance = '10km';

        $filter = new GeoDistance($key, $location, $distance);

        $expected = array(
            'geo_distance' => Map {
                $key => $location,
                'distance' => $distance,
            },
        );

        $data = $filter->toArray();

        $this->assertEquals($expected, $data);
    }

    /**
     * @group unit
     */
    public function testSetDistanceType() : void
    {
        $filter = new GeoDistance('location', array('lat' => 48.86, 'lon' => 2.35), '10km');
        $distanceType = GeoDistance::DISTANCE_TYPE_ARC;
        $filter->setDistanceType($distanceType);

        $data = $filter->toArray();

        $this->assertEquals($distanceType, /* UNSAFE_EXPR */ $data['geo_distance']['distance_type']);
    }

    /**
     * @group unit
     */
    public function testSetOptimizeBbox() : void
    {
        $filter = new GeoDistance('location', array('lat' => 48.86, 'lon' => 2.35), '10km');
        $optimizeBbox = GeoDistance::OPTIMIZE_BBOX_MEMORY;
        $filter->setOptimizeBbox($optimizeBbox);

        $data = $filter->toArray();

        $this->assertEquals($optimizeBbox, /* UNSAFE_EXPR */ $data['geo_distance']['optimize_bbox']);
    }
}
