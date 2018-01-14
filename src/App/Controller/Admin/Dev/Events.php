<?php
namespace App\Controller\Admin\Dev;

use Tk\Request;
use Dom\Template;
use \App\Controller\AdminIface;

/**
 *
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Events extends AdminIface
{

    /**
     * @var \Tk\Table
     */
    protected $table = null;


    /**
     *
     * @param Request $request
     * @throws \Tk\Exception
     */
    public function doDefault(Request $request)
    {
        $this->setPageTitle('Available Events');
        
        $this->table = new \Tk\Table('EventList');
        $this->table->setRenderer(\Tk\Table\Renderer\Dom\Table::create($this->table));

        $this->table->addCell(new \Tk\Table\Cell\Text('name'));
        $this->table->addCell(new \Tk\Table\Cell\Text('value'));
        $this->table->addCell(new \Tk\Table\Cell\Text('eventClass'));
        $this->table->addCell(new \Tk\Table\Cell\Html('doc'))->addCss('key');

        $this->table->addAction(\Tk\Table\Action\Csv::create());

<<<<<<< HEAD
        $list = $this->convertEventData(\App\Factory::getEventDispatcher()->getAvailableEvents(\App\Config::getInstance()->getSitePath()));
=======
        $list = $this->convertEventData($this->getConfig()->getEventDispatcher()->getAvailableEvents($this->getConfig()->getSitePath()));
>>>>>>> 573c23c28fe7fda9066c66f2276cc1d0f6d44197
        $this->table->setList($list);

    }

    /**
     * @param $eventData
     * @return array
     */
    protected function convertEventData($eventData) {
        $data = array();
        foreach ($eventData as $className => $eventArray) {

            foreach ($eventArray['const'] as $consName => $constData) {
                $data[] = array(
                    'name' => '\\'.$className . '::' . $consName,
                    'value' => $constData['value'],
                    'eventClass' => '\\'.$constData['event'],
                    'doc' => nl2br($constData['doc'])
                );
            }
        }
        return $data;
    }

    /**
     * @return \Dom\Template
     */
    public function show()
    {
        $template = parent::show();

        $template->replaceTemplate('table', $this->table->getRenderer()->show());

        return $template;
    }

    /**
     * DomTemplate magic method
     *
     * @return \Dom\Template
     */
    public function __makeTemplate()
    {
        $xhtml = <<<HTML
<div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-cogs fa-fw"></i> Actions
    </div>
    <div class="panel-body">
      <a href="javascript: window.history.back();" class="btn btn-default"><i class="fa fa-arrow-left"></i> <span>Back</span></a>
    </div>
  </div>

  <div class="panel panel-default">
    <div class="panel-heading">
      <i class="fa fa-empire fa-fw"></i> Available Events
    </div>
    <div class="panel-body">
      <p>The events are available for use with plugins or when adding to the system codebase.</p>
      <div var="table"></div>
    </div>
  </div>

</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}