<?php
namespace Brown298\DataTablesBundle\Tests\Model;

use Brown298\DataTablesBundle\Test\DataTable\EmptyDataTable;
use Phake;
use \Brown298\TestExtension\Test\AbstractTest;

/**
 * Class EmptyDataTableTest
 * @package Brown298\DataTablesBundle\Tests\Model
 * @author  John Brown <brown.john@gmail.com>
 */
class EmptyDataTableTest extends AbstractTest
{
    /**
     * @var Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var Symfony\Component\HttpFoundation\Request
     */
    protected $request;

    /**
     * @var Brown298\DataTablesBundle\Service\ServerProcessService
     */
    protected $dataTablesService;

    /**
     * @var \Brown298\DataTablesBundle\Test\DataTable\EmptyDataTable
     */
    protected $dataTable;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Brown298\DataTablesBundle\MetaData\Column
     */
    protected $column;

    /**
     * @var \Brown298\DataTablesBundle\MetaData\Format
     */
    protected $format;

    /**
     * @var \Symfony\Component\Templating\EngineInterface
     */
    protected $renderer;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * setUp
     */
    public function setUp()
    {
        parent::setUp();
        $this->container         = Phake::mock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->request           = Phake::mock('Symfony\Component\HttpFoundation\Request');
        $this->dataTablesService = Phake::mock('Brown298\DataTablesBundle\Service\ServerProcessService');
        $this->logger            = Phake::mock('\Psr\Log\LoggerInterface');
        $this->column            = Phake::mock('\Brown298\DataTablesBundle\MetaData\Column');
        $this->format            = Phake::mock('\Brown298\DataTablesBundle\MetaData\Column');
        $this->renderer          = Phake::mock('\Symfony\Component\Templating\EngineInterface');
        $this->translator        = Phake::mock('\Symfony\Component\Translation\TranslatorInterface');

        Phake::when($this->container)->get('logger')->thenReturn($this->logger);
        Phake::when($this->container)->get('translator')->thenReturn($this->translator);

        $this->dataTable = new EmptyDataTable();
        $this->dataTable->setContainer($this->container);
    }

    /**
     * testConstructSetsColumns
     */
    public function testConstructSetsColumns()
    {
        $columns = array('test');

        $this->dataTable = new EmptyDataTable($columns);

        $this->assertEquals($columns, $this->dataTable->getColumns());
    }

    /**
     * testSetColumns
     */
    public function testSetColumns()
    {
        $columns = array('test');

        $this->dataTable->setColumns($columns);

        $this->assertEquals($columns, $this->dataTable->getColumns());
    }

    /**
     * testSetContainer
     */
    public function testSetContainer()
    {
        $this->dataTable->setContainer($this->container);
        $this->assertEquals($this->container, $this->getProtectedValue($this->dataTable, 'container'));
    }

    /**
     * testIsAjaxRequest
     */
    public function testIsAjaxRequest()
    {
        $this->assertFalse($this->dataTable->isAjaxRequest($this->request));
        Phake::when($this->request)->isXmlHttpRequest()->thenReturn(true);
        $this->assertTrue($this->dataTable->isAjaxRequest($this->request));
    }

    /**
     * testGetData
     */
    public function testGetData()
    {
        $this->assertEquals(array(), $this->dataTable->getData($this->request));
    }

    /**
     * testGetSetDataFormatter
     */
    public function testGetSetDataFormatter()
    {
        $formatter = function($test) { return $test; };

        $this->dataTable->setDataFormatter($formatter);
        $this->assertEquals($formatter, $this->dataTable->getDataFormatter());
    }

    /**
     * testGetJsonResponseNullFormatter
     */
    public function testGetJsonResponseNullFormatter()
    {
        $result = $this->dataTable->getJsonResponse($this->request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $result);
    }

    /**
     * testProcessRequestNonAjax
     */
    public function testProcessRequestNonAjax()
    {
        $this->assertFalse($this->dataTable->processRequest($this->request));
    }

    /**
     * testProcessRequestNullDataFormatter
     */
    public function testProcessRequestNullDataFormatter()
    {
        Phake::when($this->request)->isXmlHttpRequest()->thenReturn(true);

        $result = $this->dataTable->processRequest($this->request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $result);
    }

    /**
     * testProcessRequestDataFormatter
     */
    public function testProcessRequestDataFormatter()
    {
        Phake::when($this->request)->isXmlHttpRequest()->thenReturn(true);
        $result = $this->dataTable->processRequest($this->request, function ($data){});

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $result);
    }

    /**
     * testProcessRequestDataFormatter
     */
    public function testProcessRequestGetDataFormatter()
    {
        Phake::when($this->request)->isXmlHttpRequest()->thenReturn(true);
        $this->setProtectedValue($this->dataTable,'dataFormatter',function ($data){});

        $result = $this->dataTable->processRequest($this->request);

        $this->assertInstanceOf('Symfony\Component\HttpFoundation\JsonResponse', $result);
    }

    /**
     * testGetDataValueObject
     */
    public function testGetDataValueObject()
    {
        $expectedResult = 'test';
        $row            = new test();
        $source         = 'a.test';

        $result = $this->callProtected($this->dataTable,'getDataValue', array($row, $source));

        $this->assertEquals($expectedResult, $result);
    }


    /**
     * testGetObjectValueSimple
     */
    public function testGetObjectValueSimple()
    {
        $expectedResult = 'test';
        $row            = new test();
        $source         = 'a.test';

        $result = $this->callProtected($this->dataTable, 'getObjectValue', array($row, $source));

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * testGetObjectValueDependencyObject
     */
    public function testGetObjectValueDependencyObject()
    {
        $expectedResult = 'test';
        $row            = new test();
        $row->data     = new Test();
        $source         = 'a.data.test';

        $result = $this->callProtected($this->dataTable, 'getObjectValue', array($row, $source));

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * testGetObjectValueDependencyObjectArray
     */
    public function testGetObjectValueDependencyObjectArray()
    {
        $expectedResult = array('test','test');
        $row            = new test();
        $row->data      = array(new test(), new test());
        $source         = 'a.data.test';

        $result = $this->callProtected($this->dataTable, 'getObjectValue', array($row, $source));

        $this->assertEquals($expectedResult, $result);
    }

    /**
     * testGetDatFormatterWithMetaReturnsClosure
     */
    public function testGetDatFormatterWithMetaReturnsClosure()
    {
        $this->dataTable->setMetaData(array('test'));

        $result = $this->dataTable->getDataFormatter();

        $this->assertInstanceOf('\Closure', $result);
    }

    /**
     * testGetDataFormatterClosureEmpty
     */
    public function testGetDataFormatterClosureEmpty()
    {
        $data = array();
        $expectedResults = array();

        $this->dataTable->setMetaData(array('test'));
        $formatter = $this->dataTable->getDataFormatter();

        if ($formatter instanceof \Closure) {
            $result =  $formatter($data);
        }

        $this->assertEquals($expectedResults, $result);
    }

    /**
     * testGetDataFormatterClosure
     */
    public function testGetDataFormatterClosure()
    {
        $data = array('a.test' => 'value');
        $expectedResults = array(
            array(null),
        );

        $this->dataTable->setMetaData(array('columns' => array($this->column)));
        $formatter = $this->dataTable->getDataFormatter();

        if ($formatter instanceof \Closure) {
            $result =  $formatter($data);
        }

        $this->assertEquals($expectedResults, $result);
    }

    /**
     * testGetColumnRenderedArrayParameters
     *
     * tests that passing in a parameter that has a multi dimensional array works
     *
     * ex:
     * @DataTable\Format(dataFields={"route":"users_show", "params" : { "id":"entity.id" },
     *                  "value":"entity.username" }, template="::Datatable/link.html.twig")
     *
     * <a href='{{ path(route, params) }}'>{{ value }}</a>
     */
    public function testGetColumnRenderedArrayParameters()
    {
        $row            = new test();
        $column         = $this->column;
        $column->format = $this->format;
        $expectedResult = 'test';
        $expectedArgs   = array(
            'params' => array(
                'id'=> null,
            ),
            'value' => null,
        );

        $this->format->dataFields = json_decode('{"params": {"id": "entity.id" }, "value":"entity.id"}', true);
        $this->format->template   = "{{ params|dump }}";

        Phake::when($this->container)->get('templating')->thenReturn($this->renderer);
        Phake::when($this->renderer)->render(Phake::anyParameters())->thenReturn($expectedResult);
        $this->dataTable->setContainer($this->container);

        $result = $this->callProtected($this->dataTable,'getColumnRendered', array('row'=>$row, 'column'=>$column));

        $this->assertEquals($expectedResult, $result);
        Phake::verify($this->renderer)->render($this->format->template, Phake::capture($args));
        $this->assertEquals($expectedArgs, $args);
    }
}


class test {
    public $data;
    public function getData() {
        return $this->data;
    }
    public function getTest() {
        return 'test';
    }
}
