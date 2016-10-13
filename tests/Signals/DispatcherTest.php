<?php
namespace TeaPress\Tests\Signals;

use TeaPress\Utils\Arr;
use TeaPress\Signals\Hub;
use TeaPress\Tests\Base\ServiceTestCase;

use TeaPress\Tests\Signals\Mocks\Handler;

class DispatcherTest extends ServiceTestCase
{

	/**
	* @var \TeaPress\Signals\Hub
	*/
	protected $signals;

	protected $serviceName = 'signals';

	protected $serviceClass = Hub::class;

	protected function methodTag($method)
	{
		return str_replace('::', ':', $method);
	}

	public function testListen()
	{
		$tag = $this->methodTag(__METHOD__);
		$callback = function(){
			return 'callback';
		};
		$this->signals->listen($tag, $callback);
		$this->assertTrue($this->signals->isBound($callback, $tag));
	}

	public function testHasListeners()
	{
		$tag = $this->methodTag(__METHOD__);
		$callback = function(){
			return 'callback';
		};

		$before = $this->signals->hasListeners($tag);

		$this->signals->listen($tag, $callback);

		$after = $this->signals->hasListeners($tag);

		$this->assertTrue( (!$before && $after) );
	}


	public function testFire()
	{
		$benckmark = function()
		{
			global $wp_filter, $wp_actions, $executions;


			$col_w = 44;
			$row_h = 1;
			$lcolpad = 2;
			$colpad = " ";
			$line_char = "-";

			$cell = function($text, $num = 0, $pad_char = null) use($col_w, &$row_h, $colpad){

				$chunks = str_split($text, (strlen($text) <= $col_w ? $col_w : $col_w - 3) );
				$row_h = count($chunks);
				if(is_null($pad_char))
					$pad_char = $colpad;

				$pad = $num > 0 ? str_repeat( $pad_char, $num*$col_w) : '';
				$pad .= $num > 0 ? str_repeat( $pad_char, $num) : '';

				foreach ($chunks as $key => &$chunk){

					$chunk = $row_h > 1 && $key+1 < $row_h ? $chunk . "...\n" : $chunk;
					$rpad = ($col_w - strlen($chunk)) > 1 ? str_repeat(' ', ($col_w - strlen($chunk))) : '';

					$chunk .=$rpad;
					if( $key > 0 ){
						$chunk = $pad.$chunk;
					}
				}

				return join("", $chunks);
			};


			$line = function($ncols, $char = null) use(&$row_h, $line_char, $cell, $colpad, $lcolpad, $col_w){
				$cells = [];
				$char = is_null($char) ? $line_char : $char;
				$text = str_repeat( $char, $col_w );
				for ($i=0; $i < $ncols ; $i++) {
					$cells[] = $cell( $text, $i );
				}

				$e = str_repeat( "\n", 1);
				// $e = str_repeat( "\n", $row_h);
				$row_h = 1;
				return $char . join( str_repeat($char, $lcolpad) , $cells). $char .$e;
			};

			$row = function(...$columns) use(&$row_h, $cell, $line, $colpad, $lcolpad){
				$cells = [];
				foreach ($columns as $num => $col) {
					$cells[] = $cell($col, $num);
				}
				$e = str_repeat( "\n", 1);
				// $e = str_repeat( "\n", $row_h);
				$row_h = 1;
				$l = $line( count($columns) );
				return join( str_repeat($colpad, $lcolpad) , $cells).$e.$l;
			};

			echo "\n";
			$current = current_filter();
			echo str_repeat("..........................{$current}.................................\n", 3);
			$actions = [];
			$total = 0;
			// $nactions = 0;
			// $nexec = 0;

			foreach ($wp_filter as $tag => $hooks) {
				$hooked = count(Arr::dot($hooks, false));
				$total += $hooked;
				$actions[$tag] = isset($actions[$tag]) ? $actions[$tag] + $hooked : $hooked;
			}
			$top = 100;

			arsort($actions);
			arsort($executions);
			echo "\n";
			pprint('------- Hooks -------');
			// pprint("Top {$top} Hooks", array_slice($actions, 0,$top, true));

			echo "\n".$line(3);
			echo " ".$row("  Name  ", "  # Hooks  ", "  # Calls  ");

			foreach (array_slice($actions, 0 , $top, true) as $k => $h) {
				$calls = !isset($executions[$k]) ? 0 : $executions[$k];
				echo " ".$row( $k, $h, $calls );
			}

			echo "\n".$line(3);
			pprint('Total Tags', count($actions));
			pprint('Total Hooks', number_format($total, 4) );
			pprint('Average', number_format( ($total / count($actions)), 4) );

			echo "\n".$line(3);
			echo "\n";
			pprint('------- Calls -------');
			// pprint("Top {$top} Calls", array_slice($executions, 0,$top, true));

			echo "\n".$line(3);
			echo " ".$row("  Name  ", "  # Calls  ", "  # Hooks  ");

			foreach (array_slice($executions, 0 , $top, true) as $k => $calls) {
				$h = !isset($actions[$k]) ? 0 : $actions[$k];
				echo " ".$row( $k, $calls, $h );
			}
			echo "\n".$line(3);
			pprint('Total Calls', array_sum($executions));
			pprint('Average Calls', number_format( (array_sum($executions)/count($executions) ),4 ) );
			pprint('All Average', number_format( (array_sum($executions)/count($actions) ), 4 ) );

			echo $line(3);
			echo $line(3);
			echo "\n";

		};

		// $benckmark();

		// pprint('Actions', $wp_actions); // Arr::dot($wp_filter, false));
		// add_action('shutdown', $benckmark, 9999);




		$tag = $this->methodTag(__METHOD__);

		$value = 0;

		// $this->signals->listen($tag, function($value){
		// 	return $value+1;
		// });

		// $this->signals->listen($tag, function($value){
		// 	return $value+2;
		// });

		$expected = [];

		$meths = ['listen' => false, 'bindWeak' => true];
		foreach ($meths as $meth => $weak) {
			for ($i=1; $i<=10000; $i++) {
				// $inc = $i * (1;
				if(!$weak)
					$expected[] = $i;

				$this->signals->{$meth}($tag, function($value) use($i){
					return $value+$i;
				});
			}
		}

		$response = $this->signals->fire($tag, $value);

		$this->assertEquals($expected, $response);
	}


	public function testFlushesResponses()
	{



		$tag = $this->methodTag(__METHOD__);

		$value = 0;

		$this->signals->listen($tag, function($value){
			return $value+1;
		});

		$this->signals->listen($tag, function($value){
			return $value+2;
		});

		$this->signals->listen($tag, function($value){
			return $value+3;
		});

		$this->signals->fire($tag, $value);

		$this->assertNull( $this->signals->responses($tag, null) );

	}


	public function testUntil()
	{
		$tag = $this->methodTag(__METHOD__);

		$this->signals->listen($tag, function() {
			return;
		}, 0);

		$this->signals->listen($tag, function() use($tag) {
			return 2;
		});

		$this->signals->listen($tag, function() {
			return 5;
		}, 5);

		$this->assertEquals(5, $this->signals->until($tag));
	}


	public function testFlushesHaltables()
	{
		$tag = $this->methodTag(__METHOD__);

		$response = time();

		$this->signals->listen($tag, function() {
			return;
		});

		$this->signals->listen($tag, function() use($tag) {
			return 2;
		});

		$this->signals->listen($tag, function() {
			return 3;
		});

		$this->signals->until($tag);

		$this->assertFalse($this->signals->halting($tag));
	}

}