<?php  
namespace Concrete\Package\LoginLog\Controller\SinglePage\Dashboard\System\Registration;

use Concrete\Core\Application\EditResponse;
use Concrete\Core\Page\Controller\DashboardPageController;
use Config;
use Core;
use Exception;
use Group;
use stdClass;

class LoginLog extends DashboardPageController
{
    public function view()
    {
        $this->requireAsset('core/app/editable-fields');
        $groups = array();
        $group_ids = Config::get('login_log.group_ids');

        if ($group_ids && is_array($group_ids)) {
            foreach ($group_ids as $gid) {
                $g = Group::getByID($gid);
                if (!$g) {
                    continue;
                }

                $obj = new stdClass();
                $obj->gDisplayName = $g->getGroupDisplayName();
                $obj->gID = $g->getGroupID();
                $groups[] = $obj;
            }
        }

        $this->set('groupsJSON', Core::make('helper/json')->encode($groups));
    }

    public function save()
    {
        $this->error = Core::make('helper/validation/error');

        if (Core::make('token')->validate('login_log.settings.save')) {
            $group_ids = array();
            $group_ids_post = $this->post('groups');

            if (is_array($group_ids_post) && count($group_ids_post) > 0) {
                foreach ($group_ids_post as $key => $gid) {
                    $gid = Core::make('helper/security')->sanitizeInt($gid);
                    $g = Group::getByID($gid);
                    if ($g) {
                        $group_ids[] = $g->getGroupID();
                    }
                }
            }

            $status = (bool) $this->post('status', false);

            Config::save('login_log.status', $status);
            Config::save('login_log.group_ids', $group_ids);

            $this->redirect($this->action('save_success'));
        } else {
            $this->error->add(Core::make('token')->getErrorMessage());
        }
    }

    public function save_success()
    {
        $this->set('message', t('Settings saved'));
        $this->view();
    }

    public function add_group()
    {
        $this->validate('add_group');
        $this->modifyGroup('add');
    }

    public function remove_group()
    {
        $this->validate('remove_group');
        $this->modifyGroup('remove');
    }

    /**
     * @param string $action
     */
    public function validate($action)
    {
        $token_validator = Core::make('helper/validation/token');
        if (!$token_validator->validate($action)) {
            $r = new EditResponse();
            $r->setError(new Exception('Invalid Token.'));
            $r->outputJSON();
            Core::shutdown();
        }
    }

    /**
     * @param string $task
     *
     * @throws Exception
     */
    protected function modifyGroup($task)
    {
        $g = Group::getByID(Core::make('helper/security')->sanitizeInt($this->post('gID')));
        if (is_object($g)) {
            $group_ids = Config::get('login_log.group_ids');

            if (!is_array($group_ids)) {
                $group_ids = array();
            }

            $r = new EditResponse();
            $obj = new stdClass();
            $obj->gID = $g->getGroupID();
            $obj->gDisplayName = $g->getGroupDisplayName();
            if ($task == 'add') {
                if (!in_array($g->getGroupID(), $group_ids)) {
                    $r->setAdditionalDataAttribute('groups', array($obj));
                }
            } else {
                $r->setAdditionalDataAttribute('group', $obj);
            }

            $r->outputJSON();
        } else {
            throw new Exception(t('Invalid group.'));
        }
    }
}
