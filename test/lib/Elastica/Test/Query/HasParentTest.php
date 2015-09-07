<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Query\HasParent;
use Elastica\Query\Match;
use Elastica\Query\MatchAll;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;
use Elastica\Type\Mapping;

class HasParentTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $q = new MatchAll();

        $type = 'test';

        $query = new HasParent($q, $type);

        $expectedArray = array(
            'has_parent' => Map {
                'query' => $q->toArray(),
                'type' => $type,
            },
        );

        $this->assertEquals($expectedArray, $query->toArray());
    }

    /**
     * @group unit
     */
    public function testSetScope() : void
    {
        $q = new MatchAll();

        $type = 'test';

        $scope = 'foo';

        $query = new HasParent($q, $type);
        $query->setScope($scope);

        $expectedArray = array(
            'has_parent' => Map {
                'query' => $q->toArray(),
                'type' => $type,
                '_scope' => $scope,
            },
        );

        $this->assertEquals($expectedArray, $query->toArray());
    }

    /**
     * @group functional
     */
    public function testHasParent() : void
    {
        $index = $this->_createIndex();

        $shopType = $index->getType('shop');
        $productType = $index->getType('product');
        $mapping = new Mapping();
        $mapping->setParent('shop');
        $productType->setMapping($mapping)->getWaitHandle()->join();

        $shopType->addDocuments(
            array(
                new Document('zurich', array('brand' => 'google')),
                new Document('london', array('brand' => 'apple')),
            )
        )->getWaitHandle()->join();

        $doc1 = new Document('1', array('device' => 'chromebook'));
        $doc1->setParent('zurich');

        $doc2 = new Document('2', array('device' => 'macmini'));
        $doc2->setParent('london');

        $productType->addDocument($doc1)->getWaitHandle()->join();
        $productType->addDocument($doc2)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        // All documents
        $parentQuery = new HasParent(new MatchAll(), $shopType->getName());
        $search = new Search($index->getClient());
        $results = $search->search($parentQuery)->getWaitHandle()->join();
        $this->assertEquals(2, $results->count());

        $match = new Match();
        $match->setField('brand', 'google');

        $parentQuery = new HasParent($match, $shopType->getName());
        $search = new Search($index->getClient());
        $results = $search->search($parentQuery)->getWaitHandle()->join();
        $this->assertEquals(1, $results->count());
        $result = $results->current();
        $data = $result->getData();
        $this->assertEquals($data['device'], 'chromebook');
    }
}
