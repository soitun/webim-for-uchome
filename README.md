WebIM For UChome
================================================================

为uchome提供的在线及时聊天插件.

需要PHP版本大于等于4.3, MySQL版本大于等于4.1.2

升级
-----------------------------

*	版本3.0beta之前的需要重新安装
*	版本3.0之后的直接覆盖目录即可

安装
-----------------------------

为了和bbs同步历史记录,安装时会添加历史记录数据库到ucenter数据,请确保ucenter数据库在您所安装的服务器并且可以链接.

WebIM连接时需要访问WebIM服务器, 请确保您的php环境是否可连接外部网络, 空间服务商是否打开allow\_url\_fopen.

首先将下载文件解压到UChome根目录

	.
	|-- webim
	|   |-- README.md
	|   |-- static

给与安装文件权限

	chmod 777 webim

###线上安装

1.	浏览器打开webim安装页面。例： uchome地址(http://www.uc.com/home/) -> webim安装地址(http://www.uc.com/home/webim/)

2.	配置域名，apikey确认

3.	安装完成


