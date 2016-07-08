<?php
require dirname(__FILE__).'/init.php';

switch (SYSTEM_PAGE) {

	case 'ajax:status':
		global $m,$i;
		$today = date('d');
		$count = array(
			'userSigned'  => 0,
			'userWaiting' => 0,
			'userError'   => 0,
			'allSigned'   => 0,
			'allWaiting'  => 0,
			'allNo'       => 0,
			'allError'    => 0
		);
		$signUser = $m->query("SELECT `latest`,`status` FROM `".DB_NAME."`.`".DB_PREFIX.TABLE."` WHERE `uid` = ".UID." AND `no` = '0'");
		while($countUser = $m->fetch_array($signUser)) {
			if($countUser['latest'] == $today) {
				if($countUser['status'] != '0') {
					$count['userError']++;
				} else {
					$count['userSigned']++;
				}
			} else {
				$count['userWaiting']++;
			}
		}
		echo "<br/><b>签到状态：</b>已签到 {$count['userSigned']} 个贴吧，{$count['userError']} 个出错， {$count['userWaiting']} 个贴吧等待签到";
		echo '<br/><b>您的签到数据表：</b>'.DB_PREFIX.TABLE;

		if (ROLE == 'admin') {
			foreach ($i['table'] as $value) {
				$signTab = $m->query("SELECT `latest`,`status`,`no` FROM `".DB_NAME."`.`".DB_PREFIX.$value."`");
				while($countTab = $m->fetch_array($signTab)) {
					if($countTab['no'] != '0') {
						$count['allNo']++;
					} elseif($countTab['latest'] == $today) {
						if($countTab['status'] != '0') {
							$count['allError']++;
						} else {
							$count['allSigned']++;
						}
					} else {
						$count['allWaiting']++;
					}
				}
			}	
			echo "<br/><b>签到状态[总体]：</b>已签到 {$count['allSigned']} 个贴吧，还有 {$count['allWaiting']} 个贴吧等待签到";
			echo "<br/><b>贴吧状态[总体]：</b>有 {$count['allError']} 个贴吧签到出错，{$count['allNo']} 个贴吧已被设定为忽略";
			echo '<br/><b>用户注册/添加用户首选表：</b>'.DB_PREFIX.option::get('freetable');
		}
		break;

	case 'admin:server':
		?>
		<li class="list-group-item">
			<b>PHP 版本：</b><?php echo phpversion() ?>
			<?php if(ini_get('safe_mode')) { echo '线程安全'; } else { echo '非线程安全'; } ?>
		</li>
		<?php if(version_compare('5.3', phpversion()) === 1) { echo '<li class="list-group-item"><b>PHP 版本警告：</b><font color="red">PHP 版本太低</font>，未来云签到可能不再支持当前版本 <a href="http://php.net/manual/zh/appendices.php" target="_blank">查看如何升级</a></li>'; }?>
		<?php if(get_magic_quotes_gpc()) { echo '<li class="list-group-item"><b>性能警告：</b><font color="red">魔术引号被激活</font>，云签到正以低效率模式运行 <a href="http://php.net/manual/zh/security.magicquotes.whynot.php" target="_blank">为什么不用魔术引号</a> <a href="http://php.net/manual/zh/security.magicquotes.disabling.php" target="
		_blank">如何关闭魔术引号</a></li>'; }?>
		<li class="list-group-item">
			<b>MySQL 版本：</b><?php echo $m->getMysqlVersion() ?>
		</li>
		<?php if(!empty($_SERVER['SERVER_ADDR'])) { ?>
		<li class="list-group-item">
			<b>服务器地址：</b><?php echo $_SERVER['SERVER_ADDR'] ?>
		</li>
		<?php } ?>
		<li class="list-group-item">
			<b>服务器软件：</b><?php echo $_SERVER['SERVER_SOFTWARE'] ?>
		</li>
		<li class="list-group-item">
			<b>服务器系统：</b><?php echo php_uname('a') ?>
		</li>
		<li class="list-group-item">
			<b>程序最大运行时间：</b><?php echo ini_get('max_execution_time') ?>s
		</li>
		<li class="list-group-item">
			<b>POST许可：</b><?php echo ini_get('post_max_size'); ?>
		</li>
		<li class="list-group-item">
			<b>文件上传许可：</b><?php echo ini_get('upload_max_filesize'); ?>
		</li>
		<?php
		break;

	case 'admin:update':
		$data= json_decode(substr(file_get_contents('https://raw.githubusercontent.com/yunhuan2060/OpenShift-Tieba-Cloud-Sign/master/setup/log.php'),'13'),true);
		if (!is_null($data)){
			if (YH_SYSTEM_V!=$data['n']) {
				echo  '<div class="bs-callout bs-callout-danger">
  <h4>有更新可用</h4>
  <br/>当前版本：V'.YH_SYSTEM_V.'
  <br/>最新版本：V'.$data['n'].'
  <br/>V'.$data['n'].'更新描述：'.$data[$data['n']].'
  <br/><br/>避免在流量高峰时升级，注意备份数据
</div>';
				echo '<form action="ajax.php?mod=admin:update:updnow" method="post">';
				echo '<input type="hidden" name="id" value="'.$data['n'].'">';
				echo '<input type="submit" class="btn btn-primary" value="更新到最新正式版本"><br/><br/></form>';
			} else {
				echo '<div class="alert alert-success">您当前正在使用最新版本: V'.$data['n'].'，无需更新</div>';
			}
		} else {
			echo '<div class="alert alert-info">无法连接到Github，可以尝试<a href="http://www.yunhuan.tk/2016/02/08/%E7%99%BE%E5%BA%A6%E8%B4%B4%E5%90%A7%E4%BA%91%E7%AD%BE%E5%88%B0openshift%E4%B8%93%E7%89%88/" target="_blank">手动更新</a></div>';
		}
		break;

	case 'admin:update:updnow':
		mkdir(UPDATE_CACHE,0777,true);
		if (!file_download_this('https://codeload.github.com/yunhuan2060/OpenShift-Tieba-Cloud-Sign/zip/'.$_POST['id'],UPDATE_CACHE.$_POST['id'].'.zip')) {
			DeleteFile(UPDATE_CACHE);
			msg('错误 - 更新失败：<br/><br/>无法从github下载安装包');
		}
		$z = new zip();
		$z->open(UPDATE_CACHE.$_POST['id'].'.zip');
		$z->extract(UPDATE_CACHE);
		$z->close();
		unlink(UPDATE_CACHE.$_POST['id'].'.zip');
		$floderName = UPDATE_CACHE.'OpenShift-Tieba-Cloud-Sign-'.$_POST['id'];
		if(!is_dir($floderName)){
			DeleteFile(UPDATE_CACHE);
			msg('错误 - 更新失败：<br/><br/>无法解压缩更新包');
		}
		if(CopyAll($floderName,SYSTEM_ROOT) != true){
			DeleteFile(UPDATE_CACHE);
			msg('错误 - 更新失败：<br/><br/>无法更新文件');
		}
		DeleteFile(UPDATE_CACHE);
		ReDirect('index.php?mod=admin:update&ok');
		break;

	case 'baiduid:getverify':
		global $m;
		if (option::get('bduss_num') == '-1' && ROLE != 'admin') msg('本站禁止绑定新账号');
		if (option::get('bduss_num') != '0' && ISVIP == false) {
			$count = $m->once_fetch_array("SELECT COUNT(*) AS `c` FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` WHERE `uid` = ".UID);
			if (($count['c'] + 1) > option::get('bduss_num')) msg('您当前绑定的账号数已达到管理员设置的上限<br/><br/>您当前已绑定 '.$count['c'].' 个账号，最多只能绑定 '.option::get('bduss_num').' 个账号');
		}
        $name  = !empty($_POST['bd_name']) ? $_POST['bd_name'] : die();
        $pw    = !empty($_POST['bd_pw'])   ? $_POST['bd_pw']   : die();
        $vcode = !empty($_POST['vcode'])   ? $_POST['vcode']   : '';
        $vcodestr = !empty($_POST['vcodestr']) ? $_POST['vcodestr'] : '';
		$loginResult = misc::loginBaidu($name, $pw, $vcode, $vcodestr);
		if ($loginResult[0] == -3) {
            echo '{"error":"-3","msg":"请输入验证码","vcodestr":"'.$loginResult[1].'","img":"'.$loginResult[2].'"}';
            /*
			echo '<img onclick="addbdid_getcode();" src="'.$loginResult[2].'"style="float:left;">&nbsp;&nbsp;&nbsp;请在下面输入左图中的字符<br>&nbsp;&nbsp;&nbsp;点击图片更换验证码';
			echo '<br/><br/><div class="input-group"><span class="input-group-addon">验证码</span>';
			echo '<input type="text" class="form-control" id="bd_v" name="bd_v" placeholder="请输入上图的字符" required></div><br/>';
			echo '<input type="hidden" id="vcodeStr" name="vcodestr" value="'.$loginResult[1].'"/>';
            */
		} elseif($loginResult[0] == 0) {
			if((option::get('same_pid') == '1' || option::get('same_pid') == '2') && !ISADMIN) {
				$checkSame = $m->once_fetch_array("SELECT * FROM `".DB_NAME."`.`".DB_PREFIX."baiduid` WHERE `name` = '{$loginResult[2]}'");
				if(!empty($checkSame)) {
					if(option::get('same_pid') == '2') {
						echo '{"error":"-11","msg":"你已经绑定了这个百度账号或者该账号已被其他人绑定，若要重新绑定，请先解绑"}';
					} elseif(option::get('same_pid') == '1' && $checkSame['uid'] == UID) {
						echo '{"error":"-10","msg":"你已经绑定了这个百度账号，若要重新绑定，请先解绑"}';
					}
					die;
				}
			}
			$m->query("INSERT INTO `".DB_NAME."`.`".DB_PREFIX."baiduid` (`uid`,`bduss`,`name`) VALUES  (".UID.", '{$loginResult[1]}', '{$loginResult[2]}')");
			echo '{"error":"0","msg":"获取BDUSS成功","bduss":"'.$loginResult[1].'","name":"'.$loginResult[2].'"}';
		} else {
            echo '{"error":"'.$loginResult[0].'","msg":"'.$loginResult[1].'"}';
        }
		break;

	default:
		msg('未定义操作');
		break;
}
?>