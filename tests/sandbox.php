<?php
// Run only with PHP CLI. Copies this package to a private throwaway directory.
if(PHP_SAPI!=='cli')exit;
$sandbox=sys_get_temp_dir().'/sttshoutbox-tests-'.bin2hex(random_bytes(10));
if(!mkdir($sandbox,0700))throw new RuntimeException('Cannot create test sandbox');
$copy=function($from,$to)use(&$copy){foreach(new DirectoryIterator($from)as$entry){if($entry->isDot())continue;if($entry->isLink())throw new RuntimeException('No symlinks allowed in tests');$dest=$to.'/'.$entry->getFilename();if($entry->isDir()){mkdir($dest,0700);$copy($entry->getPathname(),$dest);}else copy($entry->getPathname(),$dest);}};
$copy(dirname(__DIR__).'/Upload',$sandbox);
file_put_contents($sandbox.'/inc/adminfunctions_templates.php','<?php // In-memory test implementation is defined in run.php.');
register_shutdown_function(function()use($sandbox){
 $root=realpath($sandbox);if(!$root||basename($root)!==basename($sandbox)||dirname($root)!==realpath(sys_get_temp_dir()))return;
 $remove=function($dir)use(&$remove){foreach(new DirectoryIterator($dir)as$e){if($e->isDot())continue;if($e->isDir()&&!$e->isLink())$remove($e->getPathname());else unlink($e->getPathname());}rmdir($dir);};$remove($root);
});
