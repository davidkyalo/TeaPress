<?php
namespace TeaPress\Tests\Filesystem;

use TeaPress\Filesystem\Filesystem;
use TeaPress\Tests\Base\ServiceTestCase;
use TeaPress\Filesystem\FileNotFoundException;

/**
*
*/
class FilesystemTest extends ServiceTestCase
{
	protected $serviceName = 'files';
	protected $serviceClass = Filesystem::class;

	public function testRegisteredInIocContainer()
	{
		$this->runRegisteredTest();
	}

	public function testServiceAliases()
	{
		$this->runServiceAliasesTest();
	}

	/**
     * @expectedException TeaPress\Filesystem\FileNotFoundException
     *
     **/
	public function testRequireMissingScriptException()
	{
		$path ='/x_x_x_x/some/fake/script.php';
		$this->files->require($path);
	}

	public function testRequireMissingScriptDefault()
	{
		$path ='/x_x_x_x/some/fake/script.php';
		$this->assertNull( $this->files->require($path, null, null) );
	}

	public function testRequireScriptWithData()
	{
		$path = __DIR__.'/data/return_passed_data.php';
		$passed_data = 'This was passed to the script';

		$result = $this->files->require($path,compact('passed_data'));

		$this->assertEquals( $passed_data, $result );
	}

	public function testRequireOnce()
	{
		$path = __DIR__.'/data/return_passed_data.php';

		$count = $this->files->requireOnce($path, ['passed_data' => 1]);
		$count = $this->files->requireOnce($path, ['passed_data' => 200]);

		$this->assertEquals(1, $count);
	}
}