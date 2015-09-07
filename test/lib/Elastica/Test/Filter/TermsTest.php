<?hh
namespace Elastica\Test\Filter;

use Elastica\Document;
use Elastica\Filter\Terms;
use Elastica\Query;
use Elastica\Test\Base as BaseTest;

class TermsTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testLookup() : void
    {
        $index = $this->_createIndex();
        $type1 = $index->getType('musicians');
        $type2 = $index->getType('bands');

        //index some test data
        $type1->addDocuments(array(
            new Document('1', array('name' => 'robert', 'lastName' => 'plant')),
            new Document('2', array('name' => 'jimmy', 'lastName' => 'page')),
            new Document('3', array('name' => 'john paul', 'lastName' => 'jones')),
            new Document('4', array('name' => 'john', 'lastName' => 'bonham')),
            new Document('5', array('name' => 'jimi', 'lastName' => 'hendrix')),
        ))->getWaitHandle()->join();

        $type2->addDocument(new Document('led zeppelin', array('members' => array('plant', 'page', 'jones', 'bonham'))))->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        //use the terms lookup feature to query for some data
        $termsFilter = new Terms();
        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', null);
        $query = new Query();
        $query->setPostFilter($termsFilter);
        $results = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals($results->count(), 4, 'Terms lookup with null index');

        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', $index);
        $query->setPostFilter($termsFilter);
        $results = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals($results->count(), 4, 'Terms lookup with index as object');

        //Query with index given as string
        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', $index->getName());
        $query->setPostFilter($termsFilter);
        $results = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals($results->count(), 4, 'Terms lookup with index as string');

        //Query with array of options
        $termsFilter->setLookup('lastName', $type2, 'led zeppelin', 'members', array('index' => $index, 'cache' => false));
        $query->setPostFilter($termsFilter);
        $results = $index->search($query)->getWaitHandle()->join();
        $this->assertEquals($results->count(), 4, 'Terms lookup with options array');

        $index->delete()->getWaitHandle()->join();
    }

    /**
     * @group unit
     */
    public function testSetExecution() : void
    {
        $filter = new Terms('color', array('blue', 'green'));

        $filter->setExecution('bool');
        $this->assertEquals('bool', $filter->getParam('execution'));

        $returnValue = $filter->setExecution('bool');
        $this->assertInstanceOf('Elastica\Filter\Terms', $returnValue);
    }

    /**
     * @group unit
     */
    public function testSetTerms() : void
    {
        $field = 'color';
        $terms = array('blue', 'green');

        $filter = new Terms();
        $filter->setTerms($field, $terms);
        $expected = array('terms' => Map {$field => $terms});
        $this->assertEquals($expected, $filter->toArray());

        $returnValue = $filter->setTerms($field, $terms);
        $this->assertInstanceOf('Elastica\Filter\Terms', $returnValue);
    }

    /**
     * @group unit
     */
    public function testAddTerm() : void
    {
        $filter = new Terms('color', array('blue'));

        $filter->addTerm('green');
        $expected = array('terms' => Map {'color' => array('blue', 'green')});
        $this->assertEquals($expected, $filter->toArray());

        $returnValue = $filter->addTerm('cyan');
        $this->assertInstanceOf('Elastica\Filter\Terms', $returnValue);
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $filter = new Terms('color', array());
        $expected = array('terms' => Map {'color' => array()});
        $this->assertEquals($expected, $filter->toArray());

        $filter = new Terms('color', array('cyan'));
        $expected = array('terms' => Map {'color' => array('cyan')});
        $this->assertEquals($expected, $filter->toArray());
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testToArrayInvalidException() : void
    {
        $filter = new Terms();
        $filter->toArray();
    }
}
