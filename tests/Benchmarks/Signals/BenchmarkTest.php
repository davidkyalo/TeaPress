<?php
namespace TeaPress\Tests\Benchmarks\Signals;

use TeaPress\Utils\Arr;
use TeaPress\Utils\Str;
use TeaPress\Signals\Signals;
use TeaPress\Utils\Carbon\Carbon;
use TeaPress\Filesystem\Filesystem;
use TeaPress\Filesystem\ClassFinder;
use TeaPress\Tests\Base\ServiceTestCase;

use TeaPress\Tests\Signals\Mocks\Handler;

class BenchmarkTest extends ServiceTestCase
{
	protected $tz = 'Africa/Nairobi';
	/**
	* @var \TeaPress\Signals\Hub
	*/
	protected $signals;
	protected $filesystem;
	protected $logDirName = 'signals';
	protected $logDir;
	protected $openedLogs = [];

	protected $serviceName = 'signals';

	protected $serviceClass = Hub::class;

	protected function methodTag($method)
	{
		return str_replace('::', ':', $method);
	}

	protected function filesystem()
	{
		if(is_null($this->filesystem))
			$this->filesystem = new Filesystem;

		return $this->filesystem;
	}

	protected function logDir($path = null)
	{
		if(is_null($this->logDir)){
			$this->logDir = dirname(__DIR__).'/logs/signals';
			if(!$this->filesystem()->isDirectory($this->logDir))
				$this->filesystem()->makeDirectory($this->logDir);
		}
		return $path ? join_paths($this->logDir, $path) : $this->logDir;
	}

	protected function logPath($path = null)
	{
		if(is_null($this->logDir)){
			$this->logDir = dirname(__DIR__).'/logs/signals';
			if(!$this->filesystem()->isDirectory($this->logDir))
				$this->filesystem()->makeDirectory($this->logDir);
		}
		return $path ? join_paths($this->logDir, $path) : $this->logDir;
	}

	protected function log($name, $message, $force = false)
	{
		$filesystem = $this->filesystem();
		$name = $name.'_'.Carbon::today($this->tz)->toDateString();
		$path = $this->logPath(Str::finish($name, '.log'));
		if($filesystem->isFile($path)){
			$content = $filesystem->get($path);
			if(substr(trim($content), -3) === '---'){
				if($force)
					$filesystem->delete($path);
				else
					return false;
			}
		}

		$filesystem->append($path, $message);
		return true;
	}

	public function testPerfomance()
	{
		$signals = $this->signals;

		$finder = new ClassFinder;
		$path = dirname(TESTSPATH).'/src/Contracts';
		$contracts = $finder->findClasses($path);

		$this->benchmark($contracts, 'wrap-lazy', 'Wrap Lazy', 5, 500);
	}

	protected function benchmark($contracts, $test, $title = null, $runs = 5, $max = 1000)
	{
		$title = $title ?: ucwords($test);
		$test_time = Carbon::now($this->tz)->toTimeString();

		if(!$this->log($test, "[{$test_time}] {$title}\n"))
			return;

		$signals = $this->signals;
		$tdelta = 0;
		for ($r=1; $r <= $runs; $r++) {
			$start = microtime(true);
			$total = 0;
			$events = 0;
			foreach ($contracts as $contract) {
				$event = $signals->tag( 'contract_test_event'.$r, $contract );
				for ($i=1; $i <= $max; $i++) {
					$signals->bind($event, function() use ($i){
						return $i;
					});
				}
				$called = count($signals->fire($event, null));
				$total += $called;
				++$events;
			}
			$end = microtime(true);
			$delta = $end - $start;
			$tdelta += $delta;
			$delta = number_format($delta, 2);
			$this->log($test, " #{$r}: Callbacks: {$total}/{$events}, Time:{$delta}\n");
		}
		$delta = $tdelta/$runs;
		$delta = number_format($delta, 2);
		$tt = number_format($tdelta, 2);
		$this->log($test, " [Summary] Total Time: {$tt}, Avg Time: {$delta}\n\n");
	}
}
