<?php
return array(
	//'配置项'=>'配置值'
	'SHOW_PAGE_TRACE'	=> false,
	'TMPL_CACHE_ON'		=> false,
	'TMPL_CACHE_TIME'	=> 1,
	'APP_AUTOLOAD_PATH' => 'Common.Model,Admin.Widget',
	
	//控制器
	'ctrs' => [
		'index' => '控制台',
		'user' => '用户管理',
		'content' => '内容管理',
		'setting' => '系统设置',
		'help' => '帮助',
	],
	//所有的动作
	'actions' => [
		'index' => [
			['adminList', '管理员列表'],
			['adminEdit', '管理员编辑'],
			['adminDel',  '管理员删除'],
			['roleList',  '角色管理'],
			['roleEdit',  '角色添加编辑'],
			['roleDel',   '角色删除'],
		],
		'user'=>[
	        ['index','用户列表'],
	        ['comment','用户评论'],
	        ['message','消息管理'],
	        ['postCate','帖子分类'],
	        ['post','帖子管理'],
	        ['postComment','帖子评论'],
		    ['report','用户举报'],
	        
	    ],
		'content'=>[
			['index', 	 '内容列表'],
			['contentCate', '内容分类'],
		    ['friendLink','友情链接'],
		    ['navigation','导航管理'],
		    ['block', '区块管理']
		],
		'setting'=>[
			['index', '系统配置'],
		]
	],
	//导航
	'nav'=>[
		'index' => [
			['index', 		'概况'],
			['adminList',	'管理员'],
			['roleList', 	'角色管理'],
			['phpInfo', 	'服务器信息'],
		],
		'user'=>[
	        ['index','用户列表'],
	        ['comment','用户评论'],
	        ['message','消息管理'],
	        ['postCate','帖子分类'],
	        ['post','帖子管理'],
	        ['postComment','帖子评论'],
		    ['report','用户举报'],
	        
	    ],
		'content'=>[
			['index', 	 '内容列表'],
			['contentCate', '内容分类'],
		    ['friendLink','友情链接'],
		    ['navigation','导航管理'],
		    ['block', '区块管理']
		],
		'setting' => [
			['index', '系统设置'],
		],
		'help' => [
			['index', 	'帮助'],
			['backend', '后端编码规范'],
			['frontend', '前端项目规范'],
			['ui', 		'UI设计规范'],
			['goods', 	'产品设计规范'],
		],
	],
);