<?php

class ImportInit extends WP_UnitTestCase {

  protected $importer;
  protected $operation;

  public function setUp() {
    $plugin = WPPluginToolkitPlugin::create('CanalblogImporter', __DIR__ . '/../bootstrap.php');

    $this->importer = new CanalblogImporterImporter($plugin);
    $this->operation = new CanalblogImporterImporterConfiguration($plugin->getConfiguration());
  }

  /*
    cleanUri()
   */
	function testCleanupHttpUri() {
	  $result = $this->operation->cleanUri(' http://atoutcuisine.CaNaLblog.com/index.php ');
		$this->assertEquals('http://atoutcuisine.canalblog.com', $result);
	}

	function testCleanupSelfHostedUri() {
	  $result = $this->operation->cleanUri(' http://www.leblognotesdoliviermasbou.info/index.php ');
		$this->assertEquals('http://www.leblognotesdoliviermasbou.info', $result);
  }

	function testCleanupSelfHostedUriOverHttps() {
	  $result = $this->operation->cleanUri(' https://www.leblognotesdoliviermasbou.info/index.php ');
		$this->assertEquals('https://www.leblognotesdoliviermasbou.info', $result);
	}

  /*
    assertCanalblogByUri()
   */
  function testIsCanalblogUri() {
    $this->assertTrue($this->operation->assertCanalblogByUri('http://atoutcuisine.canalblog.com'));
  }

  function testIsnotCanalblogUri() {
    $this->assertFalse($this->operation->assertCanalblogByUri('http://www.leblognotesdoliviermasbou.info'));
  }

  /*
    assertCanalblogByHtml()
   */

  function testIsCanalblogSelfHosted() {
    // taken from http://www.leblognotesdoliviermasbou.info
    $html = ' <!DOCTYPE html> <html xmlns:fb="http://www.facebook.com/2008/fbml" xmlns:og="http://ogp.me/ns#"><head><title>Le blog-notes</title><meta name="generator" content="CanalBlog - http://www.canalblog.com" /></head><body></body></html> ';

    $this->assertTrue($this->operation->assertCanalblogByHtml($html));
  }

  function testAssertIsnotCanalblogSelfHosted() {
    $html = ' <!DOCTYPE html> <html xmlns:fb="http://www.facebook.com/2008/fbml" xmlns:og="http://ogp.me/ns#"><head><title>Le blog-notes</title><meta name="Keywords" content="Fruits,Légumes,Interprofession, Pomme de terre" /></head><body></body></html> ';

    $this->assertFalse($this->operation->assertCanalblogByHtml($html));
  }
}

