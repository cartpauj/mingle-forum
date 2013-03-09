<div class="wrap">
  <h2>Mingle Forum - <?php _e('Structure', 'mingle-forum'); ?></h2>

  <p><i>* <?php _e('Categories can be thought of as empty boxes. Great for organizing stuff, but no good without something in them. Use categories to organize your various Forums. Say you want a discussion board dedicated to classic sports cars. Then you would create a Category called "Chevrolet" and put Forums inside of it called "Corvette Sting Ray", "Aston Martin DB5", "1969 Camaro", etc.', 'mingle-forum'); ?></i></p>

  <h2 class="nav-tab-wrapper">
    <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure'); ?>" class="nav-tab main-nav nav-tab-active"><?php _e('Categories', 'mingle-forum'); ?></a>
    <a href="<?php echo admin_url('admin.php?page=mingle-forum-structure&action=forums'); ?>" class="nav-tab main-nav"><?php _e('Forums', 'mingle-forum'); ?></a>
  </h2>

  <form action="" method="post">
    <fieldset class="mf_fset">
      <legend><?php _e('Manage Categories', 'mingle-forum'); ?></legend>
      <ol id="sortable-categories" class="mf_ordered_list">
        <?php if(!empty($categories)): ?>
          <?php foreach($categories as $cat): ?>
            <li>
              <input type="hidden" name="mf_category_id[]" value="<?php echo $cat->id; ?>" />
              &nbsp;&nbsp;
              <label for="category-name-<?php echo $cat->id; ?>"><?php _e('Category Name:', 'mingle-forum'); ?></label>
              <input type="text" name="category_name[]" id="category-name-<?php echo $cat->id; ?>" value="<?php echo stripslashes($cat->name); ?>" />
              &nbsp;&nbsp;
              <label for="category-description-<?php echo $cat->id; ?>"><?php _e('Description:', 'mingle-forum'); ?></label>
              <input type="text" name="category_description[]" id="category-description-<?php echo $cat->id; ?>" value="<?php echo stripslashes($cat->description); ?>" size="50" />

              <a href="#" class="mf_remove_category" title="<?php _e('Remove this Category', 'mingle-forum'); ?>">
                <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
              </a>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
            <li>
              <input type="hidden" name="mf_category_id[]" value="new" />
              &nbsp;&nbsp;
              <label for="category-name-9999999"><?php _e('Category Name:', 'mingle-forum'); ?></label>
              <input type="text" name="category_name[]" id="category-name-9999999" value="" />
              &nbsp;&nbsp;
              <label for="category-description-9999999"><?php _e('Description:', 'mingle-forum'); ?></label>
              <input type="text" name="category_description[]" id="category-description-9999999" value="" />

              <a href="#" class="mf_remove_category" title="<?php _e('Remove this Category', 'mingle-forum'); ?>">
                <img src="<?php echo WPFURL.'images/remove.png'; ?>" width="24" />
              </a>
            </li>
        <?php endif; ?>
      </ol>

      <a href="#" id="mf_add_new_category" title="<?php _e('Add new Category', 'mingle-forum'); ?>">
        <img src="<?php echo WPFURL.'images/add.png'; ?>" width="32" />
      </a>
    </fieldset>

    <div style="margin-top:15px;">
      <input type="submit" name="mf_categories_save" value="<?php _e('Save Changes', 'mingle-forum'); ?>" class="button" />
    </div>
  </form>

</div>
