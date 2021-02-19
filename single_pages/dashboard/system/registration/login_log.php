<?php 
defined('C5_EXECUTE') or die('Access Denied.');

$token_validator = Core::make('helper/validation/token');
?>
<style type="text/css">
    .editable-fields {
        margin-top: 30px;
    }

    .group-list {
        border: 1px solid #ededed;
        padding: 10px;
        margin-top: 10px;
        margin-bottom: 10px;
        max-width: 500px;
        position: relative;
        clear: both;
    }
</style>

<form method="post" action="<?php   echo $controller->action('save') ?>">
    <?php  $token->output('login_log.settings.save'); ?>

    <div class="checkbox">
        <label>
            <?php 
            echo $form->checkbox('status', 1, Config::get('login_log.status'));
            echo t('Enable Authentication Logging');
            ?>
        </label>
    </div>

    <div class="editable-fields" data-container="editable-fields">
        <?php  echo t("Only enable logging for the groups below:"); ?>

        <div data-container="group-list" class="group-list"></div>

        <a class="btn btn-default btn-xs" data-button="assign-groups" dialog-width="640"
           dialog-height="480" dialog-modal="true"
           href="<?php  echo URL::to('/ccm/system/dialogs/group/search') ?>?filter=assign"
           dialog-title="<?php  echo t('Add Groups') ?>" dialog-modal="false"><?php  echo t('Add Group') ?></a>
    </div>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <button class="pull-right btn btn-primary" type="submit"><?php  echo t('Save') ?></button>
        </div>
    </div>
</form>

<script type="text/template" data-template="user-add-groups">
    <% _.each(groups, function(group) { %>
    <div class="row" data-editable-field-inline-commands="true" data-group-id="<%=group.gID%>">
        <input type="hidden" name="groups[]" value="<%=group.gID%>" />
        <ul class="ccm-edit-mode-inline-commands">
            <li><a href="#" data-group-id="<%=group.gID%>" data-button="delete-group"><i class="fa fa-trash-o"></i></a>
            </li>
        </ul>
        <div class="col-md-6"><span><%=group.gDisplayName%></span></div>
    </div>
    <% }); %>
</script>

<script type="text/javascript">
    $(function () {
        var _addGroupsTemplate = _.template($('script[data-template=user-add-groups]').html());
        $('div[data-container=group-list]').append(
            _addGroupsTemplate({'groups': <?php  echo $groupsJSON?>})
        );

        ConcreteEvent.subscribe('SelectGroup', function (e, data) {
            $.concreteAjax({
                url: "<?php  echo URL::to('/dashboard/system/registration/login_log/add_group')?>",
                data: {
                    gID: data.gID,
                    ccm_token: '<?php  echo $token_validator->generate('add_group') ?>'
                },
                success: function (r) {
                    $('div[data-container=group-list]').append(
                        _addGroupsTemplate({'groups': r.groups})
                    );
                    _.each(r.groups, function (group) {
                        $('div[data-container=group-list] div[data-group-id=' + group.gID + ']').addClass('animated bounceIn');
                    });
                    jQuery.fn.dialog.closeTop();
                }
            });
        });

        $('div[data-container=editable-fields]').on('click', 'a[data-button=delete-group]', function () {
            $.concreteAjax({
                url: "<?php  echo URL::to('/dashboard/system/registration/login_log/remove_group')?>",
                data: {
                    gID: $(this).attr('data-group-id'),
                    ccm_token: '<?php  echo $token_validator->generate('remove_group') ?>'
                },
                success: function (r) {
                    $('div[data-container=group-list] div[data-group-id=' + r.group.gID + ']').queue(function () {
                        $(this).addClass('animated bounceOutLeft');
                        $(this).dequeue();
                    }).delay(500).queue(function () {
                        $(this).remove();
                        $(this).dequeue();
                    });
                    jQuery.fn.dialog.closeTop();
                }
            });
            return false;
        });

        $('a[data-button=assign-groups]').dialog();
    });
</script>