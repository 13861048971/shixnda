<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html>
<html lang="zh-cn">
<head>
	<meta name="renderer" content="webkit" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge;chrome=1">
	<title><?php echo ($title); ?></title>
	<meta name="keywords" content="<?php echo ($keywords); ?>" />
	<meta name="description" content="<?php echo ($description); ?>" />
	<link rel="shortcut icon" href="Public/admin.ico" type="image/x-icon"/>
	<script> var controller = '<?php echo (CONTROLLER_NAME); ?>'; </script>
	<link rel="stylesheet" type="text/css" href="/Public/css/bootstrap.min.css" /><link rel="stylesheet" type="text/css" href="/Public/css/dashboard.css" />
	<script type="text/javascript" src="/Public/js/lib/jquery.min.js"></script><script type="text/javascript" src="/Public/js/lib/bootstrap.min.js"></script><script type="text/javascript" src="/Public/js/lib/layer/layer.js"></script>
</head>
<body>
<div class="logo"></div>
<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->
	<div class="container-fluid">
		<!-- Collect the nav links, forms, and other content for toggling -->
		<div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
		  <ul class="nav navbar-nav top-nav">
			<?php if(is_array($nav)): foreach($nav as $key=>$v): ?><li <?php if(($v["active"]) == "1"): ?>class="active"<?php endif; ?> ><a href="<?php echo ($v["url"]); ?>"><?php echo ($v["name"]); ?></a></li><?php endforeach; endif; ?>
		  </ul>
		  
		  <ul class="nav navbar-nav navbar-right admin-nav">
			<?php if(!$admin): ?><li><a href="login.html">登录</a></li>
			<?php else: ?>
			<li><a target="_blank" href="/">前台</a></li>
			<li class="dropdown">
			  <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php echo ($admin["name"]); ?> <span class="caret"></span></a>
			  <ul class="dropdown-menu" role="menu">
				<li><a href="<?php echo U('/Admin');?>">概况</a></li>
				<li class="divider"></li>
				<li><a href="<?php echo U('Index/Logout');?>">退出</a></li>
			  </ul>
			</li><?php endif; ?>
		  </ul>
		</div><!-- /.navbar-collapse -->
	</div>
</nav>

<div class="container-fluid">
      <div class="row">
        <div class="col-sm-3 col-md-2 sidebar">
			<ul class="nav nav-sidebar">
			<?php if(is_array($leftNav)): foreach($leftNav as $key=>$v): ?><li <?php if(($v["active"]) == "1"): ?>class="active"<?php endif; ?> >
				  <?php if(!$v['list']): ?><a href="<?php echo ($v["url"]); ?>"><?php echo ($v["name"]); ?></a>
				  <?php else: ?>
					<a class="has-list"><?php echo ($v["name"]); ?></a>
					<ul>
					<?php if(is_array($v["list"])): foreach($v["list"] as $key=>$vl): ?><li class="<?php echo ($vl['active'] ? 'active':''); ?>"><a href="<?php echo ($vl["url"]); ?>"><?php echo ($vl["name"]); ?></a></li><?php endforeach; endif; ?>
					</ul><?php endif; ?>
				</li><?php endforeach; endif; ?>
			</ul>
		</div>
		<div class="col-sm-9 col-sm-offset-3 col-md-10 col-md-offset-2 main">
			<h2 class="sub-header">
				<span class="right-action pull-right navbar-right">
				<?php if(is_array($rightAction)): foreach($rightAction as $key=>$v): if($v['dialog'] < 1): ?><a class="btn btn-primary" href="<?php echo ($v["url"]); ?>"  ><?php echo ($v["name"]); ?></a>
				  <?php else: ?>
				   <?php if($v['list']): ?><div class="btn-group">
					  <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
						<?php echo ($v["name"]); ?> <span class="caret"></span>
					  </button>
					  <ul class="dropdown-menu">
						<?php if(is_array($v["list"])): foreach($v["list"] as $key=>$v2): ?><li><a href="#" class="dialog" <?php if($v['dialog-lg']): ?>dialog-lg="true"<?php endif; ?> url="<?php echo ($v2["url"]); ?>" ><?php echo ($v2["name"]); ?></a></li><?php endforeach; endif; ?>
					  </ul>
					</div>
				   <?php else: ?>
					<a class="btn btn-primary dialog" <?php if($v['dialog-lg']): ?>dialog-lg="true"<?php endif; ?> url="<?php echo ($v["url"]); ?>" ><?php echo ($v["name"]); ?></a><?php endif; endif; endforeach; endif; ?>
				</span>
				<?php echo ($main_title); ?>
			</h2>

<div class="table-responsive shrink user">
	<form class="search form-inline clearfix">
		<div class="pull-left time-buttons">
		  <?php if(is_array($timeArr)): foreach($timeArr as $key=>$v): ?><a class="<?php echo ($v['cur']?"cur":""); ?>" href="<?php echo ($v["url"]); ?>"><?php echo ($v["name"]); ?></a><?php endforeach; endif; ?>
			<?php echo w('form/node', $selectDate);?>
			<?php echo w('select/index', $orderArr);?>
		</div>
		<div class="btn-group pull-right hide">
		<input  class="form-control" name="keywords" type="text" placeholder="手机号或者昵称搜索" value="<?php echo ($search["keywords"]); ?>" >
		<button  class="form-control btn-info" >搜索</button>
		</div>
	</form>
	<table class="table table-striped">
		<tr>  
		  <th width="40">#</th>
		  <th>头像</th>
		  <th>昵称</th>
		  <th>性别</th>
		  <th>注册手机号</th>
		  <th>反馈说明</th>
		  <th>发布</th>
		  <th>更新</th>
		  <th>操作</th>
		</tr>
		<?php if(is_array($list)): foreach($list as $key=>$v): ?><tr titie="<?php echo ($v["statusName"]); ?>">
			<td width="20"><?php echo ($v["id"]); ?></td>
			<td><?php if($v['avatar']): ?><a href="javascript:openInNewWindow('<?php echo ($v["avatar"]); ?>')">
						<img class="avatar" src="<?php echo ($v["avatar"]); ?>" width=25 />
					</a><?php endif; ?>
			</td>
			<td><?php echo ($v["nickname"]); ?></td>
			<td><?php echo ($v["sexName"]); ?></td>
			<td><?php echo ($v["mobile"]); ?></td>
			<td class="left"><?php echo ($v["desc"]); ?></td>
			<td><?php echo ($v["addTime"]); ?></td>
			<td><?php echo ($v["updateTime"]); ?></td>
			<td class="handle">
				<a class="dialog edit hide" href="#" url="<?php echo u('feedbackEdit','id=' . $v['id']);?>" dialogTitle="编辑评论">编辑</a>
				<a class="ajaxDel del" href="#" url="<?php echo u('feedbackDel','id=' . $v['id']);?>">删除</a>
			</td>
		</tr><?php endforeach; endif; ?>
	</table>
	<div class="pager"><?php echo ($page); ?></div>
</div>
<link rel="stylesheet" type="text/css" href="/Public/js/lib/daterangepicker/daterangepicker-bs3.css" />
<script type="text/javascript" src="/Public/js/lib/laydate/laydate.js"></script><script type="text/javascript" src="/Public/js/lib/daterangepicker/moment.min.js"></script><script type="text/javascript" src="/Public/js/lib/daterangepicker/daterangepicker.js"></script>
		</div>
	</div>
</div>
<div class="footer">
	<p>footer</p>
</div>
<script type="text/javascript" src="/Public/js/functions.js"></script><script type="text/javascript" src="/Public/js/dialog.js"></script><script type="text/javascript" src="/Public/js/admin/admin.js"></script>
</body>
</html>