<?php
namespace App\Controller\User;

use Tk\Request;

/**
 * Class Index
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @link http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class Index extends \Bs\Controller\User\Index
{

    /**
     * @param Request $request
     */
    public function doDefault(Request $request)
    {
        $this->setPageTitle('My Account');
        // TODO:
        
        
    }

    public function show()
    {
        $template = parent::show();
        
        $template->insertText('name', $this->getUser()->name);
        
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
<div class="container">
<div class="row">

  <div class="col-lg-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        <i class="fa fa-users fa-fw"></i> Welcome <span var="name"></span>
      </div>
      <div class="panel-body ">

        <p>Something spiffy.....</p>
        <p><a href="/logout.html">Logout ;-)</a></p>
      </div>
    </div>
  </div>

</div>
</div>
HTML;

        return \Dom\Loader::load($xhtml);
    }

}