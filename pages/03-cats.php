<div class="wrap">
  <div id="icon-tools" class="icon32"><br /></div>
  <h2><?php _e('Canalblog Importer', 'canalblog-importer') ?></h2>

  <p><strong><?php _e('Import Steps', 'canalblog-importer') ?></strong></p>
  <ol>
    <li><?php _e('Configuration', 'canalblog-importer') ?></li>
    <li><?php _e('Tags', 'canalblog-importer') ?></li>
    <li><strong><?php _e('Categories', 'canalblog-importer') ?></strong></li>
    <li><?php _e('Archives', 'canalblog-importer') ?></li>
    <li><?php _e('Cleanup', 'canalblog-importer') ?></li>
  </ol>

  <h3><?php _e('Categories', 'canalblog-importer') ?></h3>
  <form action="?import=canalblog" method="post">
    <?php wp_nonce_field('import-canalblog') ?>
    <input type="hidden" name="process-import" value="1" />

    <p><?php printf(__('About to import <strong>%s categories</strong>.', 'canalblog-importer'), count($categories)) ?></p>

    <p class="submit">
      <input type="submit" name="submit" class="button-primary" value="<?php echo esc_attr__('Import Categories', 'canalblog-importer') ?>" />
      <input type="submit" name="submit" class="button" value="<?php echo esc_attr__('Cancel', 'canalblog-importer') ?>" />
    </p>
  </form>
</div>