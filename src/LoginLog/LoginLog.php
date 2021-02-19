<?php 
namespace Concrete\Package\LoginLog\Src\LoginLog;

use Concrete\Core\Authentication\AuthenticationType;
use Concrete\Core\Logging\GroupLogger;
use Concrete\Core\Logging\Logger;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\User\UserInfo;
use Request;

class LoginLog
{
    /**
     * @param \Concrete\Core\User\Event\User $eu
     */
    public function onUserLogin($eu)
    {
        $app = Application::getFacadeApplication();
        $config = $app['config'];

        $u = $eu->getUserObject();

        if (version_compare($config->get('concrete.version'), '8', '>')) {
            $ui = $app->make('\Concrete\Core\User\UserInfoRepository')->getByID($u->getUserID());
        } else {
            $ui = UserInfo::getByID($u->getUserID());
        }

        // Logging is currently disabled.
        if (!$config->get('login_log.status')) {
            return null;
        }

        $db = $app->make('database')->connection();
        $group_ids = $config->get('login_log.group_ids');

        if ($group_ids) {
            $isInGroups = false;
            if (is_array($group_ids)) {
                $group_ids = implode(",", $group_ids);
                $v = array($u->getUserID(), $group_ids);
                $isInGroups = $db->fetchColumn("SELECT gID FROM UserGroups WHERE uID = ? AND gID IN (?)", $v);
            }

            // Skip logging for this user.
            if ($isInGroups === false) {
                return null;
            }
        }

        // Set Log level to INFO.
        $l = new GroupLogger("Authentication", Logger::INFO);

        /*
         * Log user login name.
         * E.g. "admin" or "user@domain.com".
         */
        $username = $u->getUserName();
        if ($config->get('concrete.user.registration.email_registration')) {
            $username = $ui->getUserEmail();
        }
        $l->write(t("User logged in: %s", $username));

        /*
         * Log user IP address.
         */
        $ip = $ui->getLastIPAddress();
        if ($ip) {
            $l->write(t("IP address: %s", $ip));
        }

        /*
         * Log authentication type.
         * E.g. "Standard", "Google", "Facebook".
         */
        $auth_type_id = $u->getLastAuthType();
        $auth_type = AuthenticationType::getByID($auth_type_id);
        if ($auth_type) {
            $l->write(t("Authentication type: %s", $auth_type->getAuthenticationTypeName()));
        }

        /*
         * Log User Agent.
         * E.g.: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36
         */
        $agent = Request::getInstance()->server->get('HTTP_USER_AGENT');
        $agent = filter_var($agent, FILTER_SANITIZE_STRING);
        if ($agent) {
            $l->write(t("User agent: %s", $agent));
        }

        $l->close();
    }
}
