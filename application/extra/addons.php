<?php

return array (
  'autoload' => false,
  'hooks' => 
  array (
    'leesignhook' => 
    array (
      0 => 'leesign',
    ),
    'user_sidenav_after' => 
    array (
      0 => 'recharge',
    ),
  ),
  'route' => 
  array (
    '/example$' => 'example/index/index',
    '/example/d/[:name]' => 'example/demo/index',
    '/example/d1/[:name]' => 'example/demo/demo1',
    '/example/d2/[:name]' => 'example/demo/demo2',
    '/leesign$' => 'leesign/index/index',
    '/qrcode$' => 'qrcode/index/index',
    '/qrcode/build$' => 'qrcode/index/build',
  ),
);