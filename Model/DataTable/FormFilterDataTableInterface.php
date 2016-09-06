<?php
namespace Brown298\DataTablesBundle\Model\DataTable;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class FormFilterDataTableInterface
 *
 * @package Brown298\DataTablesBundle\Model\DataTable
 * @author John Brown <john.brown@partnerweekly.com>
 */
interface FormFilterDataTableInterface
{

    /**
     * getFilterForm
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function getFilterForm(Request $request);

    /**
     * getFormFactory
     *
     * @return mixed
     */
    public function getFormFactory();

    /**
     * setFormFactory
     *
     * @param FormFactoryInterface $formFactory
     *
     * @return mixed
     */
    public function setFormFactory(FormFactoryInterface $formFactory = null);
} 