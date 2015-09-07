<?hh
namespace Elastica\Test;

use Elastica\Document;
use Elastica\Query;
use Elastica\ResultSet;
use Elastica\ScanAndScroll;
use Elastica\Search;
use Elastica\Test\Base as BaseTest;

class ScanAndScrollTest extends BaseTest
{
    /**
     * Full foreach test.
     *
     * @group functional
     */
    public function testForeach() : void
    {
        $scanAndScroll = new ScanAndScroll($this->_prepareSearch(), '1m', 2);
        $docCount = 0;

        /** @var ResultSet $resultSet */
        foreach (/* UNSAFE_EXPR */ $scanAndScroll as $scrollId => $resultSet) {
            $docCount += $resultSet->count();
        }

        /*
         * number of loops and documents per iteration may fluctuate
         * => only test end results
         */
        $this->assertEquals(12, $docCount);
    }

    /**
     * query size revert options.
     *
     * @group functional
     */
    public function testQuerySizeRevert() : void
    {
        $search = $this->_prepareSearch();
        $search->getQuery()->setSize(9);

        $scanAndScroll = new ScanAndScroll($search);

        $scanAndScroll->rewind();
        $this->assertEquals(9, $search->getQuery()->getParam('size'));

        $scanAndScroll->next();
        $this->assertEquals(9, $search->getQuery()->getParam('size'));
    }

    /**
     * index: 12 docs, 2 shards.
     *
     * @return Search
     */
    private function _prepareSearch() : Search
    {
        $index = $this->_createIndex('', true, 2);
        $index->refresh()->getWaitHandle()->join();

        $docs = array();
        for ($x = 1; $x <= 12; ++$x) {
            $docs[] = new Document((string) $x, array('id' => $x, 'key' => 'value'));
        }

        $type = $index->getType('scanAndScrollTest');
        $type->addDocuments($docs)->getWaitHandle()->join();
        $index->refresh()->getWaitHandle()->join();

        $search = new Search($this->_getClient());
        $search->addIndex($index)->addType($type);

        return $search;
    }
}
