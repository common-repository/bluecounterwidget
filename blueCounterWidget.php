<?php
/*
Plugin Name: blueCounterWidget
Plugin URI: http://www.blueiblog.com/ko/workspace/wp-plugin-bluecounterwidget
Description: blueCounterWidget is a simple counter widget plugin.
Description: blueCounterWidget은 간단한 카운터를 위젯으로 달 수 있습니다.　
Description: blueCounterWidgetは簡単なカウンターウィゼットです。
Description: 这Plug-In就是 WordPress Counter Widget Plug-in.
Version: 1.1.4
Author: Blueⓘ
Author URI: http://www.blueiblog.com
Author Email: bluei@blueiblog.com
*/

class BlueCounterWidget {
	
	function __construct() {
		$this->initialize();
	}
	
	function BlueCounterWidget() {
		$this->initialize();
	}
	
	function initialize() {
		add_action( 'widgets_init', array(&$this, 'register') );
		load_plugin_textdomain( 'bluecounterwidget', false, basename(__FILE__, '.php') );
		$this->checkTable();
		$this->counting();
	}
	
	function register() {
		if ( function_exists("wp_register_sidebar_widget") ) {
			$widget_ops = array('classname' => 'widget_bluecounterwidget', 'description' => __('오늘, 어제의 방문객 및 총 방문자를 위젯으로 보여주도록 하는 카운터.', 'bluecounterwidget') );
			wp_register_sidebar_widget( 'bluecounterwidget', 'blueCounter', array(&$this, 'display'), $widget_ops);
			wp_register_widget_control( 'bluecounterwidget', 'blueCounter', array(&$this, 'control'), array('width' => 250, 'height' => 100) );
		}
	}
	
	function checkTable() {
		global $wpdb, $table_prefix;
		
		$isblueCounterWidgetTable = false;
		
		$checkdb = $wpdb->get_results("SHOW TABLES", ARRAY_N);
		foreach($checkdb as $row) {
			if($row[0] == $table_prefix."blueCounterWidget") {
				$isblueCounterWidgetTable = true;
				break;
			}
		}
		
		if($isblueCounterWidgetTable == false) {
			$sql = "CREATE TABLE ".$table_prefix."blueCounterWidget ( idx int not null auto_increment, today int, yesterday int, total int, todaydate date, primary key(idx) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			$wpdb->query($sql);
			$sql = "INSERT INTO ".$table_prefix."blueCounterWidget (today, yesterday, total, todaydate) VALUES('0', '0', '0', NOW())";
			$wpdb->query($sql);
			$sql = "CREATE TABLE ".$table_prefix."blueCounterWidgetLog ( idx int not null auto_increment, ipaddr varchar(15), todaydate datetime, primary key(idx) ) ENGINE=MyISAM  DEFAULT CHARSET=utf8";
			$wpdb->query($sql);
		}
		else {
			$sql = "SELECT count(*) FROM ".$table_prefix."blueCounterWidget";
			$cnt = $wpdb->get_var($sql);
			if($cnt != "1") {
				$sql = "INSERT INTO ".$table_prefix."blueCounterWidget (today, yesterday, total, todaydate) VALUES('0', '0', '0', NOW())";
				$wpdb->query($sql);
			}
		}
	}
	
	function counting() {
		global $wpdb, $table_prefix;
		
		$sql = "SELECT distinct todaydate FROM ".$table_prefix."blueCounterWidget ORDER BY todaydate DESC LIMIT 0,1";
		$today = $wpdb->get_var($sql);
		
		if($today != date("Y-m-d")) {
			// 날짜 데이터 초기화
			$sql = "UPDATE ".$table_prefix."blueCounterWidget SET yesterday=today, today=0, todaydate='".date("Y-m-d")."'";
			$wpdb->query($sql);
			$sql = "TRUNCATE ".$table_prefix."blueCounterWidgetLog";
			$wpdb->query($sql);
		}
		
		$sql = "SELECT count(idx) FROM ".$table_prefix."blueCounterWidgetLog WHERE ipaddr='".$_SERVER['REMOTE_ADDR']."'";
		$myCnt = $wpdb->get_var($sql);
		
		if($myCnt == 0) {
			$sql = "UPDATE ".$table_prefix."blueCounterWidget SET today=today+1, total=total+1";
			$wpdb->query($sql);
			$sql = "INSERT INTO ".$table_prefix."blueCounterWidgetLog (ipaddr, todaydate) VALUES ('".$_SERVER['REMOTE_ADDR']."', NOW())";
			$wpdb->query($sql);
		}
	}
	
	function display($args) {
		global $wpdb, $table_prefix;

		$options = get_option( 'bluecounter_widget' );
		
		$options['bordercolor'] = ($options['bordercolor']) ? $options['bordercolor'] : "#BBB9B2";
		$options['bgcolor'] = ($options['bgcolor']) ? $options['bgcolor'] : "#FFFFFF";
		$options['fontcolor'] = ($options['fontcolor']) ? $options['fontcolor'] : "#333333";
		
		$sql = "SELECT * FROM ".$table_prefix."blueCounterWidget";
		$cntdata = $wpdb->get_results($sql);		
		
		echo '<li id="chcounter" class="widget '.get_class($this).'_'.__FUNCTION__.'">';
		echo '<h2 class="widgettitle">';
		echo $options['title'];
		echo '</h2>';
		echo '</li>';
		echo '<div id="blueCounter_widget" class="widget" style="margin-bottom: 20px;">';
		echo '<table width="100%" border="0" cellpadding="0" cellspacing"0" style="-moz-border-radius: 5px; -khtml-border-radius: 5px; -webkit-border-radius: 5px; border-radius: 5px; background: '.$options['bgcolor'].'; border: 1px solid '.$options['bordercolor'].';">';
		echo '<col width="25%"/><col width="25%"/><col width="25%"/><col width="25%"/>';
		echo '<tr height="30"><td colspan="4" align="center" style="font-size: 20px; font-family: verdana; color: '.$options['fontcolor'].';">'.number_format($cntdata[0]->total).'</td></tr>';
		echo '<tr height="30"><td style="padding-left: 10px; color: '.$options['fontcolor'].';">'.__( '오늘', 'bluecounterwidget' ).'</td><td style=" color: '.$options['fontcolor'].';">'.number_format($cntdata[0]->today).'</td><td style="padding-left: 10px; color: '.$options['fontcolor'].';">'.__( '어제', 'bluecounterwidget' ).'</td><td style=" color: '.$options['fontcolor'].';">'.number_format($cntdata[0]->yesterday).'</td></tr>';
		echo '</table>';
		echo '</div>';
		
	}
	
	function control()
	{
		global $wpdb, $table_prefix;
		
		$options = get_option( 'bluecounter_widget' );
		
		if ( $_POST['bluecounter-submit'] ) {
			$options['title'] = $_POST['widget_title'];
			$options['bordercolor'] = ($_POST['bordercolor']) ? $_POST['bordercolor'] : "#BBB9B2";
			$options['bgcolor'] = ($_POST['bgcolor']) ? $_POST['bgcolor'] : "#FFFFFF";
			$options['fontcolor'] = ($_POST['fontcolor']) ? $_POST['fontcolor'] : "#333333";
			
			$sql = "UPDATE ".$table_prefix."blueCounterWidget SET total='".$_POST['totalcnt']."', yesterday='".$_POST['yesterdaycnt']."', today='".$_POST['todaycnt']."'";
			$wpdb->query($sql);
			
			update_option( 'bluecounter_widget', $options );
		}
		
		$sql = "SELECT total, today, yesterday FROM ".$table_prefix."blueCounterWidget";
		$row = $wpdb->get_results($sql);
		$today = $row[0]->today;
		$yesterday = $row[0]->yesterday;
		$total = $row[0]->total;
		
		$options['bordercolor'] = ($options['bordercolor']) ? $options['bordercolor'] : "#BBB9B2";
		$options['bgcolor'] = ($options['bgcolor']) ? $options['bgcolor'] : "#FFFFFF";
		$options['fontcolor'] = ($options['fontcolor']) ? $options['fontcolor'] : "#333333";
		
		echo '<p style="text-align: left;">'.__( '타이틀', 'bluecounterwidget' ).': <input class="widefat" type="text" name="widget_title" id="widget_title" value="'.$options['title'].'" style="width:180px;" /></p>';
		echo '<p style="text-align: left;">'.__( '테두리 색', 'bluecounterwidget' ).': <input class="widefat" type="text" name="bordercolor" id="bordercolor" value="'.$options['bordercolor'].'" style="width:100px;" /></p>';
		echo '<p style="text-align: left;">'.__( '배경 색', 'bluecounterwidget' ).': <input class="widefat" type="text" name="bgcolor" id="bgcolor" value="'.$options['bgcolor'].'" style="width:100px;" /></p>';
		echo '<p style="text-align: left;">'.__( '폰트 색','bluecounterwidget' ).': <input class="widefat" type="text" name="fontcolor" id="fontcolor" value="'.$options['fontcolor'].'" style="width:100px;" /></p>';
		echo '<p style="text-align: left;">'.__( '총방문자수','bluecounterwidget' ).': <input class="widefat" type="text" name="totalcnt" id="totalcnt" value="'.$total.'" style="width:100px;" /></p>';
		echo '<p style="text-align: left;">'.__( '오늘방문자수','bluecounterwidget' ).': <input class="widefat" type="text" name="todaycnt" id="totalcnt" value="'.$today.'" style="width:100px;" /></p>';
		echo '<p style="text-align: left;">'.__( '어제방문자수','bluecounterwidget' ).': <input class="widefat" type="text" name="yesterdaycnt" id="totalcnt" value="'.$yesterday.'" style="width:100px;" /></p>';
		echo '<input type="hidden" name="bluecounter-submit" id="bluecounter-submit" value="1" />';
	}
}


// Run Widget
$blueCounterWidget = new BlueCounterWidget();
?>
