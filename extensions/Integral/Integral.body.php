<?php
if( !defined( 'MEDIAWIKI' ) ) die( "You can't run this directly." );

# Implementation for Integral, a trivial MediaWiki extension.
#
# This is the main body of the extension, providing all of the code
# needed to enable the {{CHSTAMP}} wiki variable.

# Define a constant for our variable.



//表结构 SQL
/*
  CREATE TABLE IF NOT EXISTS `integral` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned NOT NULL,
  `section` int(4) unsigned NOT NULL,
  `section_user` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_id` (`page_id`,`section`,`section_user`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;
*/

class Integral {
	/**
	 * EDITOR SUBMIT
	 */
	static function Editor(){
		//EDITOR
		$page_title = $_GET['title'];
		$page_action= $_GET['action'];
		$wpSection = $_POST['wpSection'];

		if($page_title && $page_action=='submit'){
			$dbw = wfGetDB( DB_SLAVE );

			//查询当前页面的信息
	    	$result = $dbw->selectRow(
				'page',
				'*',
				array( 'page_title' =>  $page_title),
				__METHOD__
			);
			$result = self::object_array($result);
			if($result){
				$definition = $_POST['wpTextbox1'];
	    		$definition = preg_replace( '/<!--.*?-->/s', '', $definition );
	    		$lines = preg_split('/(\r\n|\r|\n)+/',$definition);

	    		foreach ( $lines as $line ) {
	    			$m = array();
					if ( preg_match( '/^==+ *([^*:\s|]+?)\s*==+\s*$/', $line, $m ) ) {
						$section[] = $m[1];
					}
	    		}
			}
			$uid = $_SESSION['wsUserID'];

			foreach ($section as $key => $vo) {
				$info=$dbw->selectRow('integral','*',array('page_id'=> $result['page_id'],"section_title"=>$vo),__METHOD__);
				$info = self::object_array($info);
				if(!empty($info)){
					//update
					$row['section_user'] = $uid;
					$where['id'] = $info['id'];
					$dbw->update( 'integral',
						$row,
						$where,
						__METHOD__
					);
				}else{
					//Insert
					$data['id']			   = null;
					$data['page_id']       = $result['page_id'];
					$data['section']       = 1;
					$data['section_user']  = $uid;
					$data['section_title'] = $vo;
					$dbw->insert( 'integral', $data);
					
				}
			}

		}

	}
	/**
	 * 写入段落积分段
	 */
	static function inIntegral($uid,$vo){

	}
	static function registerHooks() {
		global $wgHooks, $wgExtensionCredits,$wgTitle;
		if($_SESSION['wsUserID']){
			$wgHooks['PersonalUrls'][0] = 'setBarItem';
		}
		
		//查询当前页面的信息
		function setBarItem(&$personal_urls, &$wgTitle) { 
			$dbw = wfGetDB( DB_SLAVE );
			$result = $dbw->selectRow(
				'integral_user',
				'*',
				array( 'uid' => $_SESSION['wsUserID'] ),
				__METHOD__
			);
			$result = Integral::object_array($result);
			if(empty($result)){
				$result['integral'] = 0;
			}
		    $personal_urls['Integral'] = array(
		        'text' => wfMsg( 'integralUrlTitle' ).':'.$result['integral'],
		        'href' => '/',
		    );
		}
		Integral::isWords();
		return true;
	}
	/**
	 * 判断页面标题是否为词条,
	 * 主要是判断数据 表中 [page_namespace] 字段的值
	 */
	static function isWords(){
		$page_id = "";
		//初始化数据连接对象
		$dbw = wfGetDB( DB_SLAVE );

		//查询当前页面的信息
    	$result = $dbw->selectRow(
			'page',
			'*',
			array( 'page_title' => Integral::getTitle() ),
			__METHOD__
		);
		$result = self::object_array($result);
    	//判断是否为普通页面
    	if(!$result['page_namespace']){
    		$page_id = $result['page_id'];

    		//根据页面ID 查找页面作者
    		$author = $dbw->selectRow(
				'revision',
				'*',
				array( 'rev_page' => $page_id ),
				__METHOD__,array( 'ORDER BY' => 'rev_id DESC', 'LIMIT' => 1 )
			);
    		$author = self::object_array($author);
    		$uid = $author['rev_user'];

    		// pangding
    		if($uid == $_SESSION['wsUserID']){
				return false;
			}
			if($_GET['action'] != ""){
				return false;
			}
			
    		//根据页面ID 查找页面内容
    		$content = $dbw->selectRow(
				'text',
				'*',
				array( 'old_id' => $author['rev_text_id'] ),
				__METHOD__
			);
			$content = self::object_array($content);
    		$definition = $content['old_text'];
    		$definition = preg_replace( '/<!--.*?-->/s', '', $definition );
    		$lines = preg_split('/(\r\n|\r|\n)+/',$definition);

    		foreach ( $lines as $line ) {
    			$m = array();
				if ( preg_match( '/^==+ *([^*:\s|]+?)\s*==+\s*$/', $line, $m ) ) {
					$section[] = $m[1];
				}
    		}
    		// print_r($section);exit;
    		// echo '<meta http-equiv="content-type" content="text/html;charset=utf-8">';
			if($section){
				foreach ($section as $key =>$vo) {
					$info=$dbw->selectRow('integral','*',array('page_id'=> $page_id,"section_title"=>$vo),__METHOD__);
					$info = self::object_array($info);
					if(!empty($info)){
						self::inUser($info['section_user']);
					}else{
						self::inUser($uid);
					}
				}
			}else{
				if($uid){
					self::inUser($uid);
				}
			}
    	}
	}

	/**
	 * User Integral
	 */
	function inUser($uid){
		$dbw = wfGetDB( DB_SLAVE );
		$user = $dbw->selectRow('integral_user','*',array( 'uid' => $uid),__METHOD__);
		$user = self::object_array($user);
		if($user){
			$row['integral'] = $user['integral']+1;
			$where['uid'] = $uid;
			$dbw->update( 'integral_user',
				$row,
				$where,
				__METHOD__
			);
		}else{
			$row['uid'] = $uid;
			$row['integral'] = 1;
			$dbw->insert( 'integral_user', $row);
		}
	}

	/**
	 * 获取当前网页的标题，也可在说是词条标题
	 */
	static function getTitle(){
		global $wgRequest;
		return $wgRequest->getVal( 'title' ); 
	}


	/**
	 * 调用这个函数，将其幻化为数组，然后取出对应值
	 * @param $array array
	 */
	static function object_array($array){
		if(is_object($array)){
			$array = (array)$array;
		}
		if(is_array($array)){
			foreach($array as $key=>$value){
				$array[$key] = self::object_array($value);
			}
		}
		return $array;
	}

	/**
	 * 查找页面段落积分内容是否存在
	 * @param $page_id int
	 */
	static function getPageintegral($page_id){

	}

	/**
	 * 格式化内容，得到所有的二级标题
	 * @param $content string [数据库中 rev_comment]
	 */
	static function getSectionTitle($content){

	}


	/**
	 * Creates an instance of this class from definition in MediaWiki:Gadgets-definition
	 * @param $definition String: Gadget definition
	 * @return Gadget|bool Instance of Gadget class or false if $definition is invalid
	 */
	public static function newFromDefinition( $definition ) {
		$m = array();
		if ( !preg_match( '/^\*+ *([a-zA-Z](?:[-_:.\w\d ]*[a-zA-Z0-9])?)(\s*\[.*?\])?\s*((\|[^|]*)+)\s*$/', $definition, $m ) ) {
			return false;
		}
		// NOTE: the gadget name is used as part of the name of a form field,
		//      and must follow the rules defined in http://www.w3.org/TR/html4/types.html#type-cdata
		//      Also, title-normalization applies.
		$gadget = new Gadget();
		$gadget->name = trim( str_replace( ' ', '_', $m[1] ) );
		// If the name is too long, then RL will throw an MWException when
		// we try to register the module
		if ( !ResourceLoader::isValidModuleName( $gadget->getModuleName() ) ) {
			return false;
		}
		$gadget->definition = $definition;
		$options = trim( $m[2], ' []' );

		foreach ( preg_split( '/\s*\|\s*/', $options, -1, PREG_SPLIT_NO_EMPTY ) as $option ) {
			$arr  = preg_split( '/\s*=\s*/', $option, 2 );
			$option = $arr[0];
			if ( isset( $arr[1] ) ) {
				$params = explode( ',', $arr[1] );
				$params = array_map( 'trim', $params );
			} else {
				$params = array();
			}

			switch ( $option ) {
				case 'ResourceLoader':
					$gadget->resourceLoaded = true;
					break;
				case 'dependencies':
					$gadget->dependencies = $params;
					break;
				case 'rights':
					$gadget->requiredRights = $params;
					break;
				case 'skins':
					$gadget->requiredSkins = $params;
					break;
				case 'default':
					$gadget->onByDefault = true;
					break;
				case 'targets':
					$gadget->targets = $params;
					break;
				case 'top':
					$gadget->position = 'top';
					break;
			}
		}
	}
}

?>
