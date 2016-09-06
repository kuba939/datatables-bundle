<?php
namespace Brown298\DataTablesBundle\Model\DataTable;

use Brown298\DataTablesBundle\Model\Cache\CacheBagInterface;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\PersistentCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Brown298\DataTablesBundle\Model\DataTable\DataTableInterface;

/**
 * Class AbstractDataTable
 *
 * @package Brown298\DataTablesBundle\Model\DataTable
 * @author  John Brown <brown.john@gmail.com>
 */
abstract class AbstractDataTable implements DataTableInterface, ContainerAwareInterface
{
    /**
     * @var array definition of the column as DQLName => display
     */
    protected $columns = array();

    /**
     * @var null
     */
    protected $dataFormatter = null;

    /**
     * @var null
     */
    protected $container = null;

    /**
     * @var null
     */
    protected $metaData = null;

    /**
     * @var null
     */
    protected $bulkFormView = null;

    /**
     * @var Brown298\DataTablesBundle\Model\Cache\CacheBagInterface|null
     */
    protected $cacheBag = null;

    /**
     * __construct
     *
     * @param array $columns
     */
    public function __construct(array $columns = null)
    {
        if ($columns !== null) {
            $this->columns = $columns;
        }
    }

    /**
     * @param null $cache
     */
    public function setCache(CacheBagInterface $cache = null)
    {
        $this->cacheBag = $cache;
    }

    /**
     * @return null
     */
    public function getCache()
    {
        return $this->cacheBag;
    }

    /**
     * getColumns
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * setColumns
     *
     * @param array $columns
     * @return null|void
     */
    public function setColumns(array $columns = null)
    {
        $this->columns = $columns;
    }

    /**
     * isAjaxRequest
     *
     * @param Request $request
     * @return bool
     */
    public function isAjaxRequest(Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            return true;
        }

        return false;
    }

    /**
     * @param callable|null $dataFormatter
     * @return mixed|void
     */
    public function setDataFormatter(\Closure $dataFormatter = null)
    {
        $this->dataFormatter = $dataFormatter;
    }

    /**
     * getColumnRendered
     *
     * @param $row
     * @param $column
     *
     * @return array|null
     */
    protected function getColumnRendered($row, $column)
    {
        if (isset($column->format)) {
            $args = array();

            if ($this->bulkFormView != null) {
                $args['form'] = $this->bulkFormView;
            }

            foreach($column->format->dataFields as $name => $source) {
                $args[$name] = $this->getColumnArg($row, $source);
            }

            if ($column->format->template != null) {
                $renderer = $this->container->get('templating');
                $result   = $renderer->render($column->format->template, $args);
            } else { // no render so send back the raw data
                $result = $args;
            }
        } else {
            $result = $this->getDataValue($row, $column->source);
        }

        return $result;
    }

    /**
     * getColumnArg
     *
     * @param $row
     * @param $source
     *
     * @return array|null|string
     */
    protected function getColumnArg($row, $source)
    {
        if (is_array($source)) { // recursively handle array parameters
            $result = array();

            foreach($source as $n=>$v) {
                $result[$n] = $this->getDataValue($row, $v);
            }

            return $result;
        } elseif (preg_match("/^'.*'$/", $source)) {
            return substr($source, 1, strlen($source)-2);
        } else {
            return $this->getDataValue($row, $source);
        }
    }

    /**
     * @param $row
     * @return array
     */
    public function getColumnRendering($row)
    {
        $result   = array();
        if ($this->cacheBag != null) {
            $key = $this->cacheBag->getKeyName('row_data', array(hash('md4', serialize($row))));
            if ($result = $this->cacheBag->fetch($key)) {
                $result = unserialize($result);
            }
        }

        if (empty($result)) {
            foreach($this->metaData['columns'] as $column) {
                $result[] = $this->getColumnRendered($row, $column);
            }
            if ($this->cacheBag != null) {
                $this->cacheBag->save($key, serialize($result));
            }
        }

        return $result;
    }

    /**
     * @param $row
     * @param $source
     * @return null
     */
    protected function getDataValue($row, $source)
    {
        $translator  = $this->container->get('translator');
        $result = null;
        if (is_object($row)) {
            $result = $this->getObjectValue($row, $source);
        } elseif (is_array($row)) {
            $tokens  = explode('.', $source);
            $current = array_pop($tokens);
            if (isset($row[$current])) {
                $result = $row[$current];
            } else {
                $result = $translator->trans('data_tables.unknown_value_at', array('%current%' => $current));
            }
        }

        return $result;
    }

    /**
     * getObject Value
     *
     * allows for relations based on things like faq.createdBy.id
     *
     * @param $row
     * @param $source
     * @return string
     */
    protected function getObjectValue($row, $source)
    {
        $translator  = $this->container->get('translator');
        $result      = $translator->trans('data_tables.unknown_value');
        $tokens      = explode('.', $source);
        $currentName = array_pop($tokens);
        $name        = 'get' . Inflector::classify($currentName);
        $tokenCount  = count($tokens);

        if ($tokenCount <= 1 && method_exists($row, $name)) {
            $result = $row->$name();
        } else {
            if ($tokenCount > 1) {
                $sub = $this->getObjectValue($row, implode('.', $tokens));
                if (is_object($sub) && method_exists($sub, $name)) {
                    $result = $sub->$name();
                } elseif (is_array($sub) || $sub instanceof PersistentCollection) {
                    $result          = array();
                    $remainingTokens =  explode('.', $source);
                    array_shift($remainingTokens);
                    array_shift($remainingTokens);
                    foreach ($sub as $d) {
                        $result[] = $this->getObjectValue($d, implode('.', $remainingTokens));
                    }
                }
            } elseif ($tokenCount == 0) {
                $result = $row;
            }
        }

        return $result;
    }

    /**
     * @return null
     */
    public function getDataFormatter()
    {
        if ($this->dataFormatter == null && !empty($this->metaData)) {
            $table = $this;
            $this->dataFormatter = function($data) use ($table) {
                $count   = 0;
                $results = array();

                foreach ($data as $row) {
                    $results[$count] = $table->getColumnRendering($row);
                    $count +=1;
                }

                return $results;
            };
        }

        return $this->dataFormatter;
    }


    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * getJsonResponse
     *
     * @param Request $request
     *
     * @param callable|null $dataFormatter
     *
     * @return JsonResponse
     */
    public function getJsonResponse(Request $request, \Closure $dataFormatter = null)
    {
        return new JsonResponse($this->getData($request, $dataFormatter));
    }

    /**
     * {@inheritDoc}
     */
    public function processRequest(Request $request, \Closure $dataFormatter = null)
    {
        if (!$this->isAjaxRequest($request)) {
            return false;
        }

        if ($dataFormatter !== null) {
            $dataFormatter = $this->dataFormatter;
        } elseif ($this->getDataFormatter() !== null) {
            $dataFormatter = $this->getDataFormatter();
        }

        // ensure at least a minimal formatter is used
        if ($dataFormatter === null) {
            $dataFormatter = function($data) { return $data; };
        }

        return $this->getJsonResponse($request, $dataFormatter);
    }

    /**
     * @param array $metaData
     * @return mixed|void
     */
    public function setMetaData(array $metaData = null)
    {
        $this->metaData = $metaData;
    }

    /**
     * @return null
     */
    public function getMetaData()
    {
        return $this->metaData;
    }

    /**
     * @param $bulkForm
     */
    public function setBulkFormView($bulkForm)
    {
        $this->bulkFormView = $bulkForm;
    }

    /**
     * @return null
     */
    public function getBulkFormView()
    {
        return $this->bulkFormView;
    }

}
