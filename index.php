<?php
include('config_common.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>WebIM For UChome</title>
		<link href="base.css" media="all" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<h1>WebIM For UChome</h1>
		<div id="wrap">
			<div id="menu">
				<ul>
					<li class="current"><a href="index.php">基本配置</a></li>
					<li><a href="themes.php">主题选择</a></li>
				</ul>
			</div>
			<div id="content">
				<?php echo $notice ?>
				<div class="box">
				<h3>更新基本配置</h3>
				<div class="box-c">
					<p class="box-desc">请先到NextIM网站注册</p>
					<form action="" method="post" class="form">
						<p><label for="host">服务器地址：</label><input class="text" type="text" id="host" value="<?php echo $_IMC['host']; ?>" name="host"/><span class="help">IM服务器地址</span></p>
						<p><label for="port">服务器端口：</label><input class="text" type="text" id="port" value="<?php echo $_IMC['port']; ?>" name="port"/></p>
						<p><label for="domain">注册域名：</label><input class="text" type="text" id="domain" value="<?php echo $_IMC['domain']; ?>" name="domain"/><span class="help">网站注册域名</span></p>
						<p><label for="apikey">注册apikey：</label><input class="text" type="text" id="apikey" value="<?php echo $_IMC['apikey']; ?>" name="apikey"/></p>
						<p><label for="local">本地语言：</label><select class="select" id="local" name="local">
						<option value="zh-CN" selected="<?php echo $_IMC['local'] == 'zh-CN' ? 'selected' : '' ?>">简体中文</option>
						<option value="zh-TW" selected="<?php echo $_IMC['local'] == 'zh-TW' ? 'selected' : '' ?>">繁体中文</option>
						<option value="en" selected="<?php echo $_IMC['local'] == 'en' ? 'selected' : '' ?>">English</option>
						</select>
						</p>
						<p class="actions"><input type="submit" class="submit" value="提交" /></p>
					</form>
				</div>
				</div>

			</div>
		</div>
		<div id="footer"><p><a href="http://www.nextim.cn">© 2010 NextIM</a></p></div>
	</body>
</html>

