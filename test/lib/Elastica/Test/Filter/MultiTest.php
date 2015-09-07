<?hh
namespace Elastica\Test\Filter;

use Elastica\Filter\AbstractMulti;
use Elastica\Filter\MatchAll;
use Elastica\Test\Base as BaseTest;

class AbstractMultiTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testConstruct() : void
    {
        $stub = $this->getStub();

        $this->assertEmpty($stub->getFilters());
    }

    /**
     * @group unit
     */
    public function testAddFilter() : void
    {
        $stub = $this->getStub();

        $filter = new MatchAll();
        $stub->addFilter($filter);

        $expected = array(
            $filter,
        );

        $this->assertSame($expected, $stub->getFilters());
    }

    /**
     * @group unit
     */
    public function testSetFilters() : void
    {
        $stub = $this->getStub();

        $filter = new MatchAll();
        $stub->setFilters(array($filter));

        $expected = array(
            $filter,
        );

        $this->assertSame($expected, $stub->getFilters());
    }

    /**
     * @group unit
     */
    public function testToArray() : void
    {
        $stub = $this->getStub();

        $filter = new MatchAll();
        $stub->addFilter($filter);

        $expected = array(
            $stub->getBaseName() => array(
                $filter->toArray(),
            ),
        );

        $this->assertEquals($expected, $stub->toArray());
    }

    /**
     * @group unit
     */
    public function testToArrayWithParam() : void
    {
        $stub = $this->getStub();

        $stub->setCached(true);

        $filter = new MatchAll();
        $stub->addFilter($filter);

        $expected = array(
            $stub->getBaseName() => Map {
                '_cache' => true,
                'filters' => array(
                    $filter->toArray(),
                ),
            },
        );

        $this->assertEquals($expected, $stub->toArray());
    }

    private function getStub() : \Elastica\Test\Filter\AbstractMultiDebug
    {
        return $this->getMockForAbstractClass('Elastica\Test\Filter\AbstractMultiDebug');
    }
}

class AbstractMultiDebug extends AbstractMulti
{
    public function getBaseName() : string
    {
        return parent::_getBaseName();
    }
}
