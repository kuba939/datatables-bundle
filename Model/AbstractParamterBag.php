<?php
namespace Brown298\DataTablesBundle\Model;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class AbstractParamterBag
 *
 * @package Brown298\DataTablesBundle\Model
 * @author  John Brown <brown.john@gmail.com>
 */
abstract class AbstractParamterBag extends ParameterBag
{
    /**
     * @var array
     */
    protected $parameterNames = array();

    /**
     * getVarByName
     *
     * gets a formatted variabl by name
     *
     * @param string  $name
     * @param integer $id
     *
     * @return mixed
     */
    public function getVarByName($name, $id = null)
    {
        if (!isset($this->parameterNames[$name])) {
            return null;
        }

        if (!isset($this->parameterNames[$name]['const'])) {
            return null;
        }

        if (!isset($this->parameterNames[$name]['default'])) {
            $default = null;
        } else {
            $default = $this->parameterNames[$name]['default'];
        }

        $const = $this->parameterNames[$name]['const'];

        if (stripos($const, '%') != false) {
            $const = sprintf($const, $id);
        }
        return $this->get($const, $default);
    }

    /**
     * setVarByNameId
     *
     * @param string $name
     * @param mixed $id
     * @param mixed $value
     */
    public function setVarByNameId($name, $id, $value)
    {
        $const = $this->parameterNames[$name]['const'];

        if (stripos($const, '%') != false) {
            $const = sprintf($const, $id);
        }

        $this->set($const, $value);
    }

    /**
     * setVarByName
     *
     * @param string $name
     * @param mixed $value
     */
    public function setVarByName($name, $value)
    {
        $this->set($this->parameterNames[$name]['const'], $value);
    }

}
