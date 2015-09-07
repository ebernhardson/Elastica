<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\GeoDistanceRange;
use Elastica\Query;
use Elastica\Query\MatchAll;
use Elastica\Test\Base as BaseTest;

class GeoDistanceRangeTest extends BaseTest
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
        $geoFilter = new GeoDistanceRange(
            'point',
            array('lat' => 30.0, 'lon' => 40.0),
            array('from' => '0km', 'to' => '2km')
        );

        $query = new Query(new MatchAll());
        $query->setPostFilter($geoFilter);
        $this->assertEquals(1, $type->search($query)->getWaitHandle()->join()->count());

        // Both points should be inside
        $query = new Query();
        $geoFilter = new GeoDistanceRange(
            'point',
            array('lat' => 30.0, 'lon' => 40.0),
            array('gte' => '0km', 'lte' => '40000km')
        );
        $query = new Query(new MatchAll());
        $query->setPostFilter($geoFilter);
        $index->refresh()->getWaitHandle()->join();

        $this->assertEquals(2, $type->search($query)->getWaitHandle()->join()->count());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testInvalidRange() : void
    {
        $geoFilter = new GeoDistanceRange(
            'point',
            array('lat' => 30.0, 'lon' => 40.0),
            array('invalid' => '0km', 'lte' => '40000km')
        );
    }

    /**
     * @group unit
     * @dataProvider invalidLocationDataProvider
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testInvalidLocation($location) : void
    {
        $geoFilter = new GeoDistanceRange(
            'point',
            $location,
            array('gt' => '0km', 'lte' => '40000km')
        );
    }

    /**
     * @group unit
     * @dataProvider constructDataProvider
     */
    public function testConstruct($key, $location, $ranges, $expected) : void
    {
        $filter = new GeoDistanceRange($key, $location, $ranges);

        $data = $filter->toArray();

        $this->assertEquals($expected, $data);
    }

    public function invalidLocationDataProvider() : array<array>
    {
        return array(
            array(
                array('lat' => 1.0),
            ),
            array(
                array('lon' => 1.0),
            ),
            array(
                array(),
            ),
            array(
                new \stdClass(),
            ),
            array(
                null,
            ),
            array(
                true,
            ),
            array(
                false,
            ),
        );
    }

    public function constructDataProvider() : array<array>
    {
        return array(
            array(
                'location',
                'u09tvqx',
                array(
                    'from' => '10km',
                    'to' => '20km',
                ),
                array(
                    'geo_distance_range' => Map {
                        'from' => '10km',
                        'to' => '20km',
                        'location' => 'u09tvqx',
                    },
                ),
            ),
            array(
                'location',
                'u09tvqx',
                array(
                    'to' => '20km',
                    'include_upper' => 0,
                    'from' => '10km',
                    'include_lower' => 1,
                ),
                array(
                    'geo_distance_range' => Map {
                        'to' => '20km',
                        'include_upper' => false,
                        'from' => '10km',
                        'include_lower' => true,
                        'location' => 'u09tvqx',
                    },
                ),
            ),
            array(
                'location',
                array(
                    'lon' => 2.35,
                    'lat' => 48.86,
                ),
                array(
                    'lte' => '20km',
                    'gt' => '10km',
                ),
                array(
                    'geo_distance_range' => Map {
                        'lte' => '20km',
                        'gt' => '10km',
                        'location' => array(
                            'lat' => 48.86,
                            'lon' => 2.35,
                        ),
                    },
                ),
            ),
            array(
                'location',
                array(
                    'lat' => 48.86,
                    'lon' => 2.35,
                ),
                array(
                    'lt' => '20km',
                    'gte' => '10km',
                ),
                array(
                    'geo_distance_range' => Map {
                        'lt' => '20km',
                        'gte' => '10km',
                        'location' => array(
                            'lat' => 48.86,
                            'lon' => 2.35,
                        ),
                    },
                ),
            ),
        );
    }
}
