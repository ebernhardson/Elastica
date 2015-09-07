<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\BoolNot;
use Elastica\Filter\Indices;
use Elastica\Filter\Term;
use Elastica\Index;
use Elastica\Query;
use Elastica\Test\Base as BaseTest;

class IndicesTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $expected = array(
            'indices' => Map {
                'indices' => array('index1', 'index2'),
                'filter' => array(
                    'term' => Map {'tag' => 'wow'},
                ),
                'no_match_filter' => array(
                    'term' => Map {'tag' => 'such filter'},
                ),
            },
        );
        $filter = new Indices(new Term(Map {'tag' => 'wow'}), array('index1', 'index2'));
        $filter->setNoMatchFilter(new Term(Map {'tag' => 'such filter'}));
        $this->assertEquals($expected, $filter->toArray());
    }

    /**
     * @group functional
     */
    public function testIndicesFilter() : void
    {
        $docs = array(
            new Document('1', array('color' => 'blue')),
            new Document('2', array('color' => 'green')),
            new Document('3', array('color' => 'blue')),
            new Document('4', array('color' => 'yellow')),
        );

        $index1 = $this->_createIndex();
        $index1->addAlias('indices_filter')->getWaitHandle()->join();
        $index1->getType('test')->addDocuments($docs)->getWaitHandle()->join();
        $index1->refresh()->getWaitHandle()->join();

        $index2 = $this->_createIndex();
        $index2->addAlias('indices_filter')->getWaitHandle()->join();
        $index2->getType('test')->addDocuments($docs)->getWaitHandle()->join();
        $index2->refresh()->getWaitHandle()->join();

        $filter = new Indices(new BoolNot(new Term(Map {'color' => 'blue'})), array($index1->getName()));
        $filter->setNoMatchFilter(new BoolNot(new Term(Map {'color' => 'yellow'})));
        $query = new Query();
        $query->setPostFilter($filter);

        // search over the alias
        $index = $this->_getClient()->getIndex('indices_filter');
        $results = $index->search($query)->getWaitHandle()->join();

        // ensure that the proper docs have been filtered out for each index
        $this->assertEquals(5, $results->count());
        foreach ($results->getResults() as $result) {
            $data = $result->getData();
            $color = $data['color'];
            if ($result->getIndex() === $index1->getName()) {
                $this->assertNotEquals('blue', $color);
            } else {
                $this->assertNotEquals('yellow', $color);
            }
        }
    }

    /**
     * @group unit
     */
    public function testSetIndices() : void
    {
        $client = $this->_getClient();
        $index1 = $client->getIndex('index1');
        $index2 = $client->getIndex('index2');

        $indices = array('one', 'two');
        $filter = new Indices(new Term(Map {'color' => 'blue'}), $indices);
        $this->assertEquals($indices, $filter->getParam('indices'));

        $indices[] = 'three';
        $filter->setIndices($indices);
        $this->assertEquals($indices, $filter->getParam('indices'));

        $filter->setIndices(array($index1, $index2));
        $expected = array($index1->getName(), $index2->getName());
        $this->assertEquals($expected, $filter->getParam('indices'));

        $returnValue = $filter->setIndices($indices);
        $this->assertInstanceOf('Elastica\Filter\Indices', $returnValue);
    }

    /**
     * @group unit
     */
    public function testAddIndex() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('someindex');

        $filter = new Indices(new Term(Map {'color' => 'blue'}), array());

        $filter->addIndex($index);
        $expected = array($index->getName());
        $this->assertEquals($expected, $filter->getParam('indices'));

        $filter->addIndex('foo');
        $expected = array($index->getName(), 'foo');
        $this->assertEquals($expected, $filter->getParam('indices'));

        $returnValue = $filter->addIndex('bar');
        $this->assertInstanceOf('Elastica\Filter\Indices', $returnValue);
    }
}
