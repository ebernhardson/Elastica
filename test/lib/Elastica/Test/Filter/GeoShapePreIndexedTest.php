<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\AbstractGeoShape;
use Elastica\Filter\GeoShapePreIndexed;
use Elastica\Query\Filtered;
use Elastica\Query\MatchAll;
use Elastica\Test\Base as BaseTest;

class GeoShapePreIndexedTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testGeoProvided() : void
    {
        $index = $this->_createIndex();
        $indexName = $index->getName();
        $type = $index->getType('type');
        $otherType = $index->getType('other_type');

        // create mapping
        $mapping = new \Elastica\Type\Mapping($type, array(
            'location' => array(
                'type' => 'geo_shape',
            ),
        ));
        $type->setMapping($mapping)->getWaitHandle()->join();

        // create other type mapping
        $otherMapping = new \Elastica\Type\Mapping($type, array(
            'location' => array(
                'type' => 'geo_shape',
            ),
        ));
        $otherType->setMapping($otherMapping)->getWaitHandle()->join();

        // add type docs
        $type->addDocument(new \Elastica\Document('1', array(
            'location' => array(
                'type' => 'envelope',
                'coordinates' => array(
                    array(0.0, 50.0),
                    array(50.0, 0.0),
                ),
            ),
        )))->getWaitHandle()->join();

        // add other type docs
        $otherType->addDocument(new \Elastica\Document('2', array(
            'location' => array(
                'type' => 'envelope',
                'coordinates' => array(
                    array(25.0, 75.0),
                    array(75.0, 25.0),
                ),
            ),
        )))->getWaitHandle()->join();

        $index->optimize()->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $gsp = new GeoShapePreIndexed(
            'location', '1', 'type', $indexName, 'location'
        );
        $gsp->setRelation(AbstractGeoShape::RELATION_INTERSECT);

        $expected = array(
            'geo_shape' => array(
                'location' => array(
                    'indexed_shape' => array(
                        'id' => '1',
                        'type' => 'type',
                        'index' => $indexName,
                        'path' => 'location',
                    ),
                    'relation' => $gsp->getRelation(),
                ),
            ),
        );

        $this->assertEquals($expected, $gsp->toArray());

        $query = new Filtered(new MatchAll(), $gsp);
        $results = $index->getType('type')->search($query)->getWaitHandle()->join();

        $this->assertEquals(1, $results->count());

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group unit
     */
    public function testSetRelation() : void
    {
        $gsp = new GeoShapePreIndexed('location', '1', 'type', 'indexName', 'location');
        $gsp->setRelation(AbstractGeoShape::RELATION_INTERSECT);
        $this->assertEquals(AbstractGeoShape::RELATION_INTERSECT, $gsp->getRelation());
        $this->assertInstanceOf('Elastica\Filter\GeoShapePreIndexed', $gsp->setRelation(AbstractGeoShape::RELATION_INTERSECT));
    }
}
