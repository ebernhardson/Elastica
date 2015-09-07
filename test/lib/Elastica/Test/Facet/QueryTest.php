<?hh
namespace Elastica\Test\Facet;

use Elastica\Document;
use Elastica\Facet\Query as FacetQuery;
use Elastica\Query;
use Elastica\Query\Term;
use Elastica\Test\Base as BaseTest;

class QueryTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testFilter() : void
    {
        $client = $this->_getClient();
        $index = $client->getIndex('test');
        $index->create(array(), true)->getWaitHandle()->join();
        $type = $index->getType('helloworld');

        $type->addDocument(new Document('1', array('color' => 'red')))->getWaitHandle()->join();
        $type->addDocument(new Document('2', array('color' => 'green')))->getWaitHandle()->join();
        $type->addDocument(new Document('3', array('color' => 'blue')))->getWaitHandle()->join();

        $index->refresh()->getWaitHandle()->join();

        $termQuery = new Term(Map {'color' => 'red'});

        $facet = new FacetQuery('test');
        $facet->setQuery($termQuery);

        $query = new Query();
        $query->addFacet($facet);

        $resultSet = $type->search($query)->getWaitHandle()->join();

        $facets = $resultSet->getFacets();

        $this->assertEquals(1, $facets['test']['count']);
    }
}
