<?hh
namespace Elastica\Test\Facet;

use Elastica\Document;
use Elastica\Facet\Statistical;
use Elastica\Query;
use Elastica\Query\MatchAll;
use Elastica\Test\Base as BaseTest;

/**
 * @todo Add test for Statistical with setScript
 */
class StatisticalTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testStatisticalWithSetField() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('helloworld');

        $doc = new Document('1', array('price' => 10));
        $type->addDocument($doc)->getWaitHandle()->join();
        $doc = new Document('2', array('price' => 35));
        $type->addDocument($doc)->getWaitHandle()->join();
        $doc = new Document('2', array('price' => 45));
        $type->addDocument($doc)->getWaitHandle()->join();

        $facet = new Statistical('stats');
        $facet->setField('price');

        $query = new Query();
        $query->addFacet($facet);
        $query->setQuery(new MatchAll());

        $index->refresh()->getWaitHandle()->join();

        $response = $type->search($query)->getWaitHandle()->join();
        $facets = $response->getFacets();

        $this->assertEquals(55, $facets['stats']['total']);
        $this->assertEquals(10, $facets['stats']['min']);
        $this->assertEquals(45, $facets['stats']['max']);
    }

    /**
     * @group functional
     */
    public function testStatisticalWithSetFields() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('helloworld');

        $doc = new Document('1', array('price' => 10, 'price2' => 20));
        $type->addDocument($doc)->getWaitHandle()->join();
        $doc = new Document('2', array('price' => 35, 'price2' => 70));
        $type->addDocument($doc)->getWaitHandle()->join();
        $doc = new Document('2', array('price' => 45, 'price2' => 90));
        $type->addDocument($doc)->getWaitHandle()->join();

        $facet = new Statistical('stats');
        $facet->setFields(array('price', 'price2'));

        $query = new Query();
        $query->addFacet($facet);
        $query->setQuery(new MatchAll());

        $index->refresh()->getWaitHandle()->join();

        $response = $type->search($query)->getWaitHandle()->join();
        $facets = $response->getFacets();

        $this->assertEquals(165, $facets['stats']['total']);
        $this->assertEquals(10, $facets['stats']['min']);
        $this->assertEquals(90, $facets['stats']['max']);
    }
}
