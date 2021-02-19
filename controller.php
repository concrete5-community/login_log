<?php 
namespace Concrete\Package\LoginLog;

use Concrete\Package\LoginLog\Src\LoginLog\LoginLog;
use Config;
use Events;
use Package;
use Page;
use SinglePage;

class Controller extends Package
{
    protected $pkgHandle = 'login_log';
    protected $appVersionRequired = '5.7.4';
    protected $pkgVersion = '1.0';

    protected $single_pages = array(
        '/dashboard/system/registration/login_log' => array(
            'cName' => 'Login Log',
        ),
    );

    public function getPackageName()
    {
        return t("Login Log");
    }

    public function getPackageDescription()
    {
        return t("Keep verbose logs of each authentication.");
    }

    public function on_start()
    {
        // Triggered in /concrete/controllers/single_page/login.php
        Events::addListener('on_user_login', function($eu) {
            $rl = new LoginLog();
            $rl->onUserLogin($eu);
        }, 10);
    }

    public function install()
    {
        $pkg = parent::install();

        $this->installPages($pkg);

        Config::save('login_log.status', true);
    }

    /**
     * @param Package $pkg
     */
    protected function installPages($pkg)
    {
        foreach ($this->single_pages as $path => $value) {
            if (!is_array($value)) {
                $path = $value;
                $value = array();
            }
            $page = Page::getByPath($path);
            if (!$page || $page->isError()) {
                $single_page = SinglePage::add($path, $pkg);

                if ($value) {
                    $single_page->update($value);
                }
            }
        }
    }
}
