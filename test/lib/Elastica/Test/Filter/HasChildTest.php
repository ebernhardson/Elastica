<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\HasChild;
use Elastica\Index;
use Elastica\Query\MatchAll;
use Elastica\Test\Base as BaseTest;

class HasChildTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $q = new MatchAll();

        $type = 'test';

        $filter = new HasChild($q, $type);

        $expectedArray = array(
            'has_child' => Map {
                'query' => $q->toArray(),
                'type' => $type,
            },
        );

        $this->assertEquals($expectedArray, $filter->toArray());
    }

    /**
     * @group functional
     */
    public function testSetType() : void
    {
        $index = $this->prepareSearchData();

        $filter = new HasChild(new MatchAll(), 'type_name');
        $this->assertEquals('type_name', $filter->getParam('type'));

        $filter->setType('new_type_name');
        $this->assertEquals('new_type_name', $filter->getParam('type'));

        $type = $index->getType('foo');
        $filter = new HasChild(new MatchAll(), $type);
        $this->assertEquals('foo', $filter->getParam('type'));

        $type = $index->getType('bar');
        $filter->setType($type);
        $this->assertEquals('bar', $filter->getParam('type'));

        $returnValue = $filter->setType('last');
        $this->assertInstanceOf('Elastica\Filter\HasChild', $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetMinimumChildrenCount() : void
    {
        $query = new MatchAll();
        $filter = new HasChild($query, 'test');

        $filter->setMinimumChildrenCount(2);
        $this->assertEquals(2, $filter->getParam('min_children'));

        $returnValue = $filter->setMinimumChildrenCount(2);
        $this->assertInstanceOf('Elastica\Filter\HasChild', $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetMaximumChildrenCount() : void
    {
        $query = new MatchAll();
        $filter = new HasChild($query, 'test');

        $filter->setMaximumChildrenCount(10);
        $this->assertEquals(10, $filter->getParam('max_children'));

        $returnValue = $filter->setMaximumChildrenCount(10);
        $this->assertInstanceOf('Elastica\Filter\HasChild', $returnValue);
    }

    /**
     * @group unit
     */
    public function testFilterInsideHasChild() : void
    {
        $f = new \Elastica\Filter\MatchAll();

        $type = 'test';

        $filter = new HasChild($f, $type);

        $expectedArray = array(
            'has_child' => Map {
                'filter' => $f->toArray(),
                'type' => $type,
            },
        );

        $this->assertEquals($expectedArray, $filter->toArray());
    }

    /**
     * @group functional
     */
    public function testFilterInsideHasChildSearch() : void
    {
        $index = $this->prepareSearchData();

        $f = new \Elastica\Filter\Term();
        $f->setTerm('user', 'child1');
        $filter = new HasChild($f, 'child');

        $searchQuery = new \Elastica\Query();
        $searchQuery->setPostFilter($filter);
        $searchResults = $index->search($searchQuery)->getWaitHandle()->join();

        $this->assertEquals(1, $searchResults->count());

        $result = $searchResults->current()->getData();
        $expected = array('id' => 'parent1', 'user' => 'parent1', 'email' => 'parent1@test.com');

        $this->assertEquals($expected, $result);
    }

    /**
     * @group functional
     */
    public function testQueryInsideHasChildSearch() : void
    {
        $index = $this->prepareSearchData();

        $f = new \Elastica\Query\Term();
        $f->setTerm('user', 'child1');
        $filter = new HasChild($f, 'child');

        $searchQuery = new \Elastica\Query();
        $searchQuery->setPostFilter($filter);
        $searchResults = $index->search($searchQuery)->getWaitHandle()->join();

        $this->assertEquals(1, $searchResults->count());

        $result = $searchResults->current()->getData();
        $expected = array('id' => 'parent1', 'user' => 'parent1', 'email' => 'parent1@test.com');

        $this->assertEquals($expected, $result);
    }

    /**
     * @group functional
     */
    public function testTypeInsideHasChildSearch() : void
    {
        $index = $this->prepareSearchData();

        $f = new \Elastica\Query\Match();
        $f->setField('alt.name', 'testname');
        $filter = new HasChild($f, 'child');

        $searchQuery = new \Elastica\Query();
        $searchQuery->setPostFilter($filter);
        $searchResults = $index->search($searchQuery)->getWaitHandle()->join();

        $this->assertEquals(1, $searchResults->count());

        $result = $searchResults->current()->getData();
        $expected = array('id' => 'parent2', 'user' => 'parent2', 'email' => 'parent2@test.com');

        $this->assertEquals($expected, $result);
    }

    private function prepareSearchData() : Index
    {
        $client = $this->_getClient();
        $index = $client->getIndex('has_child_test');
        $index->create(array(), true)->getWaitHandle()->join();

        $parentType = $index->getType('parent');

        $childType = $index->getType('child');
        $childMapping = new \Elastica\Type\Mapping($childType);
        $childMapping->setParent('parent');
        $childMapping->send()->getWaitHandle()->join();

        $altType = $index->getType('alt');
        $altDoc = new Document('alt1', array('name' => 'altname'));
        $altType->addDocument($altDoc)->getWaitHandle()->join();

        $parent1 = new Document('parent1', array('id' => 'parent1', 'user' => 'parent1', 'email' => 'parent1@test.com'));
        $parentType->addDocument($parent1)->getWaitHandle()->join();
        $parent2 = new Document('parent2', array('id' => 'parent2', 'user' => 'parent2', 'email' => 'parent2@test.com'));
        $parentType->addDocument($parent2)->getWaitHandle()->join();

        $child1 = new Document('child1', array('id' => 'child1', 'user' => 'child1', 'email' => 'child1@test.com'));
        $child1->setParent('parent1');
        $childType->addDocument($child1)->getWaitHandle()->join();
        $child2 = new Document('child2', array('id' => 'child2', 'user' => 'child2', 'email' => 'child2@test.com'));
        $child2->setParent('parent2');
        $childType->addDocument($child2)->getWaitHandle()->join();
        $child3 = new Document('child3', array('id' => 'child3', 'user' => 'child3', 'email' => 'child3@test.com', 'alt' => array(array('name' => 'testname'))));
        $child3->setParent('parent2');
        $childType->addDocument($child3)->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        return $index;
    }
}
