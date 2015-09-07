<?hh
namespace Elastica\Test;

use Elastica\Param;
use Elastica\Test\Base as BaseTest;
use Elastica\Util;

class ParamTest extends BaseTest
{
    /**
     * @group unit
     */
    public function testToArrayEmpty() : void
    {
        $param = new Param();
        $this->assertInstanceOf('Elastica\Param', $param);
        $this->assertEquals(array($this->_getFilterName($param) => Map {}), $param->toArray());
    }

    /**
     * @group unit
     */
    public function testSetParams() : void
    {
        $param = new Param();
        $params = Map {'hello' => 'word', 'nicolas' => 'ruflin'};
        $param->setParams($params);

        $this->assertInstanceOf('Elastica\Param', $param);
        $this->assertEquals(array($this->_getFilterName($param) => $params), $param->toArray());
    }

    /**
     * @group unit
     */
    public function testSetGetParam() : void
    {
        $param = new Param();

        $key = 'name';
        $value = 'nicolas ruflin';

        $params = Map {$key => $value};
        $param->setParam($key, $value);

        $this->assertEquals($params, $param->getParams());
        $this->assertEquals($value, $param->getParam($key));
    }

    /**
     * @group unit
     */
    public function testAddParam() : void
    {
        $param = new Param();

        $key = 'name';
        $value = 'nicolas ruflin';

        $param->addParam($key, $value);

        $this->assertEquals(Map {$key => array($value)}, $param->getParams());
        $this->assertEquals(array($value), $param->getParam($key));
    }

    /**
     * @group unit
     */
    public function testAddParam2() : void
    {
        $param = new Param();

        $key = 'name';
        $value1 = 'nicolas';
        $value2 = 'ruflin';

        $param->addParam($key, $value1);
        $param->addParam($key, $value2);

        $this->assertEquals(Map {$key => array($value1, $value2)}, $param->getParams());
        $this->assertEquals(array($value1, $value2), $param->getParam($key));
    }

    /**
     * @group unit
     * @expectedException \Elastica\Exception\InvalidException
     */
    public function testGetParamInvalid() : void
    {
        $param = new Param();

        $param->getParam('notest');
    }

    /**
     * @group unit
     */
    public function testHasParam() : void
    {
        $param = new Param();

        $key = 'name';
        $value = 'nicolas ruflin';

        $this->assertFalse($param->hasParam($key));

        $param->setParam($key, $value);
        $this->assertTrue($param->hasParam($key));
    }

    protected function _getFilterName(@\Elastica\Param $filter) : string
    {
        return Util::getParamName($filter);
    }
}
