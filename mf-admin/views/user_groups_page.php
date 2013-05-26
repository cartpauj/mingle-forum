<div class="wrap">
  <h2>Mingle Forum - <?php _e('User Groups', 'mingle-forum'); ?></h2>

  <form action="" method="post">
    <fieldset class="mf_fset">
      <legend><?php _e('Manage User Groups', 'mingle-forum'); ?></legend>
      <ol id="user-groups" class="mf_ordered_list">
        <?php if(!empty($user_groups)): ?>
          <?php foreach($user_groups as $group): ?>
            <li class="ui-state-default mf_user_group_li_item">
              <input type="hidden" name="mf_user_group_id[]" value="<?php echo $group->id; ?>" />
              &nbsp;&nbsp;
              <label for="user-group-name-<?php echo $group->id; ?>"><?php _e('Name:', 'mingle-forum'); ?></label>
              <input type="text" name="user_group_name[]" id="user-group-name-<?php echo $group->id; ?>" value="<?php echo htmlentities(stripslashes($group->name), ENT_QUOTES); ?>" />
              &nbsp;&nbsp;
              <label for="user-group-description-<?php echo $group->id; ?>"><?php _e('Description:', 'mingle-forum'); ?></label>
              <input type="text" name="user_group_description[]" id="user-group-description-<?php echo $group->id; ?>" value="<?php echo htmlentities(stripslashes($group->description), ENT_QUOTES); ?>" size="40" />
              &nbsp;&nbsp;
              <input type="checkbox" name="user_group_auto_add[]" id="user_group_auto_add-<?php echo $group->id; ?>" <?php checked($group->auto_add); ?> />
              <label for="user_group_auto_add-<?php echo $group->id; ?>"><?php _e('Auto-add new Users', 'mingle-forum'); ?></label>
              &nbsp;&nbsp;
              <a href="#" class="button"><?php _e('Manage Users', 'mingle-forum'); ?></a>

              <a href="#" class="mf_remove_user_group" title="<?php _e('Remove this User Group', 'mingle-forum'); ?>">
                <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
              </a>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="ui-state-default mf_user_group_li_item">
            <input type="hidden" name="mf_user_group_id[]" value="new" />
            &nbsp;&nbsp;
            <label for="user-group-name-9999999"><?php _e('Name:', 'mingle-forum'); ?></label>
            <input type="text" name="user_group_name[]" id="user-group-name-9999999" value="" />
            &nbsp;&nbsp;
            <label for="user-group-description-9999999"><?php _e('Description:', 'mingle-forum'); ?></label>
            <input type="text" name="user_group_description[]" id="user-group-description-9999999" value="" size="40" />
            &nbsp;&nbsp;
            <input type="checkbox" name="user_group_auto_add[]" id="user_group_auto_add-9999999" />
            <label for="user_group_auto_add-9999999"><?php _e('Auto-add new Users', 'mingle-forum'); ?></label>

            <a href="#" class="mf_remove_user_group" title="<?php _e('Remove this User Group', 'mingle-forum'); ?>">
              <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
            </a>
          </li>
        <?php endif; ?>
      </ol>

      <a href="#" id="mf_add_new_user_group" title="<?php _e('Add new User Group', 'mingle-forum'); ?>">
        <img src="<?php echo WPFURL.'images/add.png'; ?>" width="32" />
      </a>
    </fieldset>

    <div style="margin-top:15px;">
      <input type="submit" name="mf_user_groups_save" value="<?php _e('Save Changes', 'mingle-forum'); ?>" class="button" />
    </div>
  </form>

</div>
