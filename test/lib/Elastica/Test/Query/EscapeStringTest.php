<?hh
namespace Elastica\Test\Query;

use Elastica\Document;
use Elastica\Index;
use Elastica\Query\QueryString;
use Elastica\Test\Base as BaseTest;
use Elastica\Type;
use Elastica\Util;

class EscapeStringTest extends BaseTest
{
    /**
     * @group functional
     */
    public function testSearch() : void
    {
        $index = $this->_createIndex();
        $index->getSettings()->setNumberOfReplicas(0)->getWaitHandle()->join();

        $type = new Type($index, 'helloworld');

        $doc = new Document('1', array(
            'email' => 'test@test.com', 'username' => 'test 7/6 123', 'test' => array('2', '3', '5'), )
        );
        $type->addDocument($doc)->getWaitHandle()->join();

        // Refresh index
        $index->refresh()->getWaitHandle()->join();

        $queryString = new QueryString(Util::escapeTerm('test 7/6'));
        $resultSet = $type->search($queryString)->getWaitHandle()->join();

        $this->assertEquals(1, $resultSet->count());
    }
}
