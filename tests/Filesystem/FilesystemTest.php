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
		$path = __DIR__.'/data/required_once.php';
		$this->files->require($path, ['passed_data' => "Data"]);

		$result = $this->files->requireOnce($path, ['passed_data' => "Data"]);
		$this->assertTrue($result);
	}

	public function testFindFiles()
	{
		$fsys = $this->files;
		$path = __DIR__ .'/data';

		$fd_files = [];
		foreach ($fsys->findFiles(dirname(__DIR__))->name('*Test')->sortByName() as $key => $file) {
			$fd_files[] = $file->getPathname();
		}

		$fd_dirs = [];
		foreach ($fsys->findDirs( dirname(__DIR__))->name('later')->name('Mocks') as $key => $v) {
			$fd_dirs[] = $v->getPathname();
		}

		// pprint("Finder Files", $fsys->findFiles(dirname(__DIR__))->name('*Test.php')->get('real_path'));
		// pprint("Glob Files", $fsys->files(dirname(__DIR__), true, [ 'notName' => '*Test.php', 'path' => ['Core', 'Config'] ]));
		// pprint("Finder Dirs", $fd_dirs);
		// pprint("Glob Dirs", $fsys->directories($path));
	}

	public function testRequireAll()
	{
		$fsys = $this->files;
		$path = __DIR__ .'/data';

		$results = $this->files->requireAll($path, ['passed_data' => 'I was just passed']);

		// pprint("Results", $results);
	}
}