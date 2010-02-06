<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer') ?></h2>

  <p><strong><?php _e('Import Steps') ?></strong></p>
  <ol>
    <li><?php _e('Configuration') ?></li>
    <li><strong><?php _e('Tags') ?></strong></li>
    <li><?php _e('Categories') ?></li>
    <li><?php _e('Archives') ?></li>
    <li><?php _e('Cleanup') ?></li>
  </ol>

  <h3><?php _e('Tags') ?></h3>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <p><?php printf(__('About to import <strong>%s tags</strong>.'), count($tags)) ?></p>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary" value="<?php echo esc_attr__('Import Tags') ?>" />
      <input type="submit" name="submit" class="button" value="<?php echo esc_attr__('Cancel') ?>" />
    </p>
  </form>
</div>