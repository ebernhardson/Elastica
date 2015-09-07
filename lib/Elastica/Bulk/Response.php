<?hh
namespace Elastica\Bulk;

use Elastica\Bulk\Action;
use Elastica\Response as BaseResponse;

class Response extends BaseResponse
{
    /**
     * @var \Elastica\Bulk\Action
     */
    protected Action $_action;

    /**
     * @var string
     */
    protected string $_opType;

    /**
     * @param array|string          $responseData
     * @param \Elastica\Bulk\Action $action
     * @param string                $opType
     */
    public function __construct($responseData, Action $action, $opType)
    {
        parent::__construct($responseData);

        $this->_action = $action;
        $this->_opType = $opType;
    }

    /**
     * @return \Elastica\Bulk\Action
     */
    public function getAction() : Action
    {
        return $this->_action;
    }

    /**
     * @return string
     */
    public function getOpType() : string
    {
        return $this->_opType;
    }
}
