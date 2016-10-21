<?php
namespace TeaPress\Tests\Core\Application;

use TeaPress\Utils\Str;

class PathsTest extends BaseTestCase
{
	protected $serviceName='app.shared';

	protected function getBasePath()
	{
		return dirname( dirname( dirname(__DIR__) ) );
	}

	protected function methodAsPath($method)
	{
		$path = strtolower( str_replace(["\\", ":"], [" ", " "], $method) );
		return Str::snake( Str::studly($path) );
	}

	public function testSetBasePath()
	{
		$this->app->setBasePath( $this->getBasePath() );

		$this->assertEquals( $this->getBasePath(), $this->app->basePath());
	}

	public function testGetPath()
	{
		$this->assertEquals( $this->getBasePath(), $this->app->getPath('base'));
	}

	public function testUsePath()
	{
		$key = $this->methodAsPath(__METHOD__);

		$path = join_paths( $this->getBasePath(), $key);

		$this->app->usePath($key, $path);

		$this->assertEquals( $path, $this->app->getPath($key));
	}


	public function testAddPath()
	{
		$key = $this->methodAsPath(__METHOD__);

		$path = (array) join_paths( $this->getBasePath(), $key);

		$this->app->usePath($key, $path);

		$another = join_paths( $this->getBasePath(), $key, 'other');

		$this->app->addPath( $key, $another );

		$path[] = $another;

		$this->assertEquals( $path, $this->app->getPath($key));
	}

	public function testAddPathNamed()
	{
		$key = $this->methodAsPath(__METHOD__);

		$path = [ 'root' => join_paths( $this->getBasePath(), $key)];

		$this->app->usePath($key, $path);

		$another = join_paths( $this->getBasePath(), $key, 'other');

		$this->app->addPath( $key, $another, 'another' );

		$path['another'] = $another;

		$this->assertEquals( $path, $this->app->getPath($key));
	}


	/**
	* @expectedException InvalidArgumentException
	*
	**/
	public function testAddPathThrowsExceptionIfOriginalIsNotArray()
	{
		$key = $this->methodAsPath(__METHOD__);

		$path = join_paths( $this->getBasePath(), $key);

		$this->app->usePath($key, $path);

		$another = join_paths( $this->getBasePath(), $key, 'other');

		$this->app->addPath( $key, $another);
	}


	public function testCanAddPathsForConfig()
	{
		$app = $this->newApp();

		$key = $this->methodAsPath(__METHOD__);

		$path = join_paths( $this->getBasePath(), $key);

		$app->addPath( 'config', $path );

		$this->assertEquals( (array) $path, $app->getPath('config'));
	}


	public function testGetPathFluent()
	{
		$app = $this->newApp();

		$key = $this->methodAsPath(__METHOD__);

		$path = join_paths( $this->getBasePath(), $key);

		$app->usePath('fluent', $path );

		$this->assertEquals( $path, $app->fluentPath());
	}

	public function testUsePathFluent()
	{
		$app = $this->newApp();

		$key = $this->methodAsPath(__METHOD__);

		$path = join_paths( $this->getBasePath(), $key);

		$app->useUFluentPath($path);

		$this->assertEquals( $path, $app->getPath('u_fluent'));
	}


	public function testAddPathFluent()
	{
		$app = $this->newApp();

		$key = $this->methodAsPath(__METHOD__);

		$path = (array) join_paths( $this->getBasePath(), $key);

		$app->useFluentArrayPath($path);

		$other = join_paths( $this->getBasePath(), $key, 'other');

		$app->addFluentArrayPath( $other );

		$path[] = $other;

		$this->assertEquals( $path, $app->getPath('fluent_array'));
	}


	public function testBoundPaths()
	{
		$app = $this->newApp();

		$names = ['base', 'config', 'lang', 'assets', 'storage', 'fake', 'very_fake'];

		$key = $this->methodAsPath(__METHOD__);

		$app->setBasePath($this->getBasePath() );

		$app->useFakePath( join_paths( $this->getBasePath(), $key, 'fake') );

		$app->useVeryFakePath( join_paths( $this->getBasePath(), $key, 'veryfake') );

		$expected = [];
		$actual = [];

		foreach ($names as $name) {
			$expected[$name] = $app->{"{$name}Path"}();
			$actual[$name] = $app["path.{$name}"];
		}

		$this->assertEquals( $expected, $actual);
	}


	public function testPathTo()
	{
		$app = $this->newApp();

		$app->setBasePath($this->getBasePath() );

		$expected = join_paths($this->getBasePath(), 'somewhere', 'fake');
		$actual = $app->pathTo( 'somewhere', 'fake' );

		$this->assertEquals( $expected, $actual);
	}


}