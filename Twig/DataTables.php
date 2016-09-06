<?php
namespace Brown298\DataTablesBundle\Twig;

use \Brown298\DataTablesBundle\Model\DataTable\DataTableInterface;

/**
 * Class DataTables
 *
 * @package Brown298\DataTablesBundle\Twig
 * @author John Brown <brown.john@gmail.com>
 */
class DataTables extends \Twig_Extension
{
    /**
     * @var
     */
    protected $count = 0;
    protected $defaults = array(
        'table_template'  => 'Brown298DataTablesBundle::table.html.twig',
        'script_template' => 'Brown298DataTablesBundle::script.html.twig',
        'id'              => 'dataTable',
        'bProcessing'     => 1,
        'bServerSide'     => 1,
        'bLengthChange'   => 0,
        'bFilter'         => 0,
        'bDeferLoading'   => 0,
        'bSort'           => 1,
        'order'           => [],
        'sPaginationType' => 'full_numbers',
        'bInfo'           => 0,
        'bPaginate'       => 1,
        'bHidePaginate'   => 1,
        'path'            => '#',
        'iDisplayLength'  => -1,
        'table_class'     => 'display dataTable table table-striped',
        'aaData'          => null,
        'twigVars'        => array(),
        'customParams'    => array(),
    );

    /**
     * @var array
     */
    protected $params;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var Twig_Environment
     */
    private $environment;

    /**
     * @var \Brown298\DataTablesBundle\Model\DataTable\DataTableInterface
     */
    private $dataTable = null;

    /**
     * getFunctions
     *
     * @return array
     */
    public function getFunctions()
    {
        return array(
            'addDataTable'     => new \Twig_SimpleFunction('addDataTable', array($this, 'addDataTable'), array(
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                )),
            'addDataTableJs'     => new \Twig_SimpleFunction('addDataTableJs', array($this, 'addDataTableJs'), array(
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                )),
            'addDataTableHtml'     => new \Twig_SimpleFunction('addDataTableHtml', array($this, 'addDataTableHtml'), array(
                    'is_safe' => array('html'),
                    'needs_environment' => true,
                )),
        );
    }

    /**
     * getFilters
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            'convertFunctions' => new \Twig_SimpleFilter('convertFunctions', array($this, 'convertFunctions'), array(
                'needs_environment' => true,
            )),
        );
    }

    /**
     * convertParamToFunctions
     *
     * @param $data
     * @param $valueArray
     * @param $replaceKeys
     *
     * @return mixed
     */
    protected function convertParamToFunctions(\Twig_Environment $environment, $data, &$valueArray, &$replaceKeys)
    {
        $this->environment = $environment;
        foreach($data as $key => &$value) {
            if (is_array($value) || is_object($value)) {
                $value = $this->convertParamToFunctions($this->environment, $value, $valueArray, $replaceKeys);
            } elseif (preg_match('/^function.*/', $value)
                || $this->isJsonObject($value)) {
                $valueArray[]  = $value;
                $value         = "%{$key}%";
                $replaceKeys[] = "\"{$value}\"";
            }
        }

        return $data;
    }

    /**
     * convertFunctions
     *
     * @param $rawJson
     *
     * @return string
     */
    public function convertFunctions(\Twig_Environment $environment, $rawJson)
    {
        $this->environment = $environment;
        $data        = json_decode($rawJson);
        $valueArray  = array();
        $replaceKeys = array();

        $data = $this->convertParamToFunctions($this->environment, $data, $valueArray, $replaceKeys);

        $resultJson = json_encode($data);
        $resultJson = str_replace($replaceKeys, $valueArray, $resultJson);

        return $resultJson;
    }

    /**
     * isJsonObject
     *
     * @param $string
     *
     * @return bool
     */
    protected function isJsonObject($string)
    {
        $evalObj = json_decode($string);
        $result  =  is_object($evalObj) || is_array($evalObj);
        return $result;
    }

    /**
     * addDataTable
     *
     * @param array $columns
     * @param array $params
     *
     * @return string
     */
    public function addDataTable(\Twig_Environment $environment, $columns, $params = array())
    {
        $this->environment = $environment;
        $this->initProperties($columns, $params);

        return $this->renderJs() .$this->renderTable();
    }

    /**
     * addDataTableJs
     *
     * @param array $columns
     * @param array $params
     *
     * @return string
     */
    public function addDataTableJs(\Twig_Environment $environment, $columns, $params = array())
    {
        $this->environment = $environment;
        $this->initProperties($columns, $params);

        return $this->renderJs();
    }
    /**
     * addDataTableHtml
     *
     * @param array $columns
     * @param array $params
     *
     * @return string
     */
    public function addDataTableHtml(\Twig_Environment $environment, $columns, $params = array())
    {
        $this->environment = $environment;
        $this->initProperties($columns, $params);

        return $this->renderTable();
    }

    /**
     * @param $params
     * @return array
     */
    protected function buildParams($params)
    {
        if (!is_array($params)) {
            $params = array();
        }

        if ($this->dataTable != null) {
            $this->buildDefaults();
        }

        $params = array_merge($this->defaults, $params);
        return $params;
    }

    /**
     *
     */
    protected function buildDefaults()
    {
        $meta = $this->dataTable->getMetaData();

        if (is_array($meta)) {
            // table defaults
            if (isset($meta['table'])) {
                $table = $meta['table'];
                $this->defaults['id']              = $table->id;
                $this->defaults['bDeferLoading']   = $table->deferLoading;
                $this->defaults['bServerSide']     = $table->serverSideProcessing;
                $this->defaults['bInfo']           = $table->info;
                $this->defaults['bLengthChange']   = $table->changeLength;
                $this->defaults['bProcessing']     = $table->processing;
                $this->defaults['iDisplayLength']  = $table->displayLength;
                $this->defaults['bPaginate']       = $table->paginate;
                $this->defaults['bSort']           = $table->sortable;
                $this->defaults['bFilter']         = $table->searchable;
                $this->defaults['sPaginationType'] = $table->paginationType;
            }

            // column data
            if (isset($meta['columns'])) {
                $columns = $meta['columns'];
                $this->defaults['columnDefs'] = $this->buildColumnDefs($columns);
            }
        }
    }

    /**
     * @param array $columns
     * @return array
     */
    protected function buildColumnDefs(array $columns = array())
    {
        $count   = 0;
        $results = array();

        foreach($columns as $column) {
            $data = array();

            $data['targets'] = $count;
            
            if ($column->sortable == false) {
                $data['orderable']    = false;
            }

            if ($column->searchable == false) {
                $data['bSearchable']    = false;
            }

            if ($column->visible == false) {
                $data['bVisible']    = false;
            }

            if ($column->class != null) {
                $data['className'] = $column->class;
            }

            if ($column->width != null) {
                $data['sWidth'] = $column->width;
            }

            if ($column->defaultSort) {
                $this->count = $count;
                $data['orderData'] = $count;
	    }

            if ($column->stype != null) {
                $data['sType'] = $column->stype;
	    }

            if (!empty($data)) {
                $results[] = $data;
            } else {
                $results[] = null;
            }
            $count+=1;
        }

        return $results;
    }

    /**
     * renderJs
     *
     * @return string
     */
    public function renderJs(\Twig_Environment $environment = null)
    {
        if ($environment!= null) {
            $this->environment = $environment;
        }
        $args = array_merge($this->params['twigVars'], array(
                'columns'   => $this->columns,
                'rawParams' => $this->params,
                'params'    => $this->buildJsParams(),
            ));

        return $this->environment->render($this->params['script_template'], $args);
    }

    /**
     * buildJsParams
     *
     */
    protected function buildJsParams()
    {
        $this->params['customParams'] = (!isset($this->params['customParams'])) ? array() : $this->params['customParams'];
        $keys    = array_merge(array(
            'aaData',
            'aaSorting',
            'aaSortingFixed',
            'aoColumnDefs',
            'columnDefs',
            'bLengthChange',
            'bFilter',
            'order',
            'bDeferLoading',
            'bPaginate',
            'bProcessing',
            'bServerSide',
            'bSort',
            'bInfo',
            'fnDrawCallback',
            'fnRowCallback',
            'fnServerData',
            'iDisplayLength',
            'sPaginationType'
        ), array_keys($this->params['customParams']));
        $results = array();

        // custom conversions
        if (isset($this->params['order'])) {
            $this->params['order'] = [$this->count, 'asc'];
        }
        
        if (isset($this->params['path'])) {
            $results['sAjaxSource'] = $this->params['path'];
        }
        if (isset($this->params['customSearchForm'])) {
            $results['fnServerData'] = 'function ( sSource, aoData, fnCallback ) { var searchValues = jQuery("'
                . $this->params['customSearchForm'] . '").serializeArray(); for ( var i=0; i < searchValues.length; i++)'
                . ' { aoData.push(searchValues[i]); } jQuery.getJSON( sSource, aoData, function (json) {fnCallback(json)} ); }';
        }
        
        if (isset($this->params['aaData']) && isset($this->params['aoColumnDefs'])) {
            $this->params['columnDefs'] = $this->params['aoColumnDefs'];
            unset($this->params['aoColumnDefs']);
        }
        
        if (isset($this->params['bHidePaginate']) && $this->params['bHidePaginate']) {
            $this->params['fnDrawCallback'] = 'function() { if (jQuery(".dataTables_paginate a").length <=5) {jQuery(".dataTables_paginate").hide();} else {jQuery(".dataTables_paginate").show();} }';
        }

        // build the final params
        foreach ($keys as $key) {
            if (isset($this->params[$key]) || isset($this->params['customParams'][$key])) {
                if (isset($this->params[$key]) && (preg_match('/^aa.+/', $key) || preg_match('/^ao.+/', $key))) {
                    $results[$key] = json_encode($this->params[$key]);
                } elseif(isset($this->params['customParams'][$key])) {
                    $results[$key] = $this->params['customParams'][$key];
                } else {
                    $results[$key] = $this->params[$key];
                }
            }
        }

        return $results;
    }

    /**
     * renderTable
     *
     * @return string
     */
    public function renderTable(\Twig_Environment $environment = null)
    {
        if ($environment!= null) {
            $this->environment = $environment;
        }
        $args = array_merge($this->params['twigVars'], array(
                'columns' => $this->columns,
                'params'  => $this->params,
        ));
        return $this->environment->render($this->params['table_template'], $args);
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'data_tables';
    }

    /**
     * @param $columns
     * @param $params
     */
    private function initProperties($columns, $params)
    {
        if ($columns instanceof DataTableInterface) {
            $this->dataTable = $columns;
            $this->columns = $this->dataTable->getColumns();
        } else {
            $this->columns = $columns;
        }

        $this->params = $this->buildParams($params);
    }
}
