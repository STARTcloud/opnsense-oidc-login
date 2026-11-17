<?php
namespace OPNsense\OIDCLogin;

class IndexController extends \OPNsense\Base\IndexController
{
    public function indexAction()
    {
        $this->view->generalForm = $this->getForm("general");
        $this->view->formDialogUser = $this->getForm("dialogUser");
        $this->view->pick('OPNsense/OIDCLogin/index');
    }
}
