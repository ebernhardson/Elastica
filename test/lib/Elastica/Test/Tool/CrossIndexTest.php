<?hh
namespace Elastica\Test\Tool;

use Elastica\Document;
use Elastica\Test\Base;
use Elastica\Tool\CrossIndex;
use Elastica\Type;

class CrossIndexTest extends Base
{
    /**
     * Test default reindex.
     *
     * @group functional
     */
    public function testReindex() : void
    {
        $oldIndex = $this->_createIndex(null, true, 2);
        $this->_addDocs($oldIndex->getType('crossIndexTest'), 10);

        $newIndex = $this->_createIndex(null, true, 2);

        $this->assertInstanceOf(
            'Elastica\Index',
            CrossIndex::reindex($oldIndex, $newIndex)->getWaitHandle()->join()
        );

        $this->assertEquals(10, $newIndex->count()->getWaitHandle()->join());
    }

    /**
     * Test reindex type option.
     *
     * @group functional
     */
    public function testReindexTypeOption() : void
    {
        $oldIndex = $this->_createIndex('', true, 2);
        $type1 = $oldIndex->getType('crossIndexTest_1');
        $type2 = $oldIndex->getType('crossIndexTest_2');

        $docs1 = $this->_addDocs($type1, 10);
        $docs2 = $this->_addDocs($type2, 10);

        $newIndex = $this->_createIndex(null, true, 2);

        // \Elastica\Type
        CrossIndex::reindex($oldIndex, $newIndex, array(
            CrossIndex::OPTION_TYPE => $type1,
        ))->getWaitHandle()->join();
        $this->assertEquals(10, $newIndex->count()->getWaitHandle()->join());
        $newIndex->deleteDocuments($docs1)->getWaitHandle()->join();

        // string
        CrossIndex::reindex($oldIndex, $newIndex, array(
            CrossIndex::OPTION_TYPE => 'crossIndexTest_2',
        ))->getWaitHandle()->join();
        $this->assertEquals(10, $newIndex->count()->getWaitHandle()->join());
        $newIndex->deleteDocuments($docs2)->getWaitHandle()->join();

        // array
        CrossIndex::reindex($oldIndex, $newIndex, array(
            CrossIndex::OPTION_TYPE => array(
                'crossIndexTest_1',
                $type2,
            ),
        ))->getWaitHandle()->join();
        $this->assertEquals(20, $newIndex->count()->getWaitHandle()->join());
    }

    /**
     * Test default copy.
     *
     * @group functional
     */
    public function testCopy() : void
    {
        $oldIndex = $this->_createIndex(null, true, 2);
        $newIndex = $this->_createIndex(null, true, 2);

        $oldType = $oldIndex->getType('copy_test');
        $oldMapping = array(
            'name' => array(
                'type' => 'string',
                'store' => true,
            ),
        );
        $oldType->setMapping($oldMapping)->getWaitHandle()->join();
        $docs = $this->_addDocs($oldType, 10);

        // mapping
        $this->assertInstanceOf(
            'Elastica\Index',
            CrossIndex::copy($oldIndex, $newIndex)->getWaitHandle()->join()
        );

        $newMapping = $newIndex->getType('copy_test')->getMapping()->getWaitHandle()->join();
        if (!isset($newMapping['copy_test']['properties']['name'])) {
            $this->fail('could not request new mapping');
        }

        $this->assertEquals(
            $oldMapping['name'],
            $newMapping['copy_test']['properties']['name']
        );

        // document copy
        $this->assertEquals(10, $newIndex->count()->getWaitHandle()->join());
        $newIndex->deleteDocuments($docs)->getWaitHandle()->join();

        // ignore mapping
        $ignoredType = $oldIndex->getType('copy_test_1');
        $this->_addDocs($ignoredType, 10);

        CrossIndex::copy($oldIndex, $newIndex, array(
            CrossIndex::OPTION_TYPE => $oldType,
        ))->getWaitHandle()->join();

        $this->assertFalse($newIndex->getType($ignoredType->getName())->exists()->getWaitHandle()->join());
        $this->assertEquals(10, $newIndex->count()->getWaitHandle()->join());
    }

    /**
     * @param Type $type
     * @param int  $docs
     *
     * @return array
     */
    private function _addDocs(Type $type, @int $docs) : array
    {
        $insert = array();
        for ($i = 1; $i <= $docs; ++$i) {
            $insert[] = new Document((string) $i, array('_id' => $i, 'key' => 'value'));
        }

        $type->addDocuments($insert)->getWaitHandle()->join();
        $type->getIndex()->refresh()->getWaitHandle()->join();

        return $insert;
    }
}
