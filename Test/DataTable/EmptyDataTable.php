<?php
namespace Brown298\DataTablesBundle\Test\DataTable;

use Brown298\DataTablesBundle\Model\DataTable\AbstractDataTable as AbstractBaseDataTable;
use Brown298\DataTablesBundle\Model\DataTable\DataTableInterface as BaseDataTableInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EmptyDataTable
 *
 * @package Brown298\DataTablesBundle\Model
 * @author  John Brown <brown.john@gmail.com>
 */
class EmptyDataTable extends AbstractBaseDataTable implements BaseDataTableInterface
{
    /**
     * getData
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array|mixed
     */
    public function getData(Request $request)
    {
        return array();
    }
}
