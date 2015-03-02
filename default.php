<?php
define( 'MW_API', true );
if ( !function_exists( 'version_compare' ) || version_compare( PHP_VERSION, '5.3.2' ) < 0 ) {
	require dirname( __FILE__ ) . '/includes/PHPVersionError.php';
	wfPHPVersionError( 'default.php' );
}
require __DIR__ . '/includes/WebStart.php';

?>
<!DOCTYPE html>
<html lang="<?php echo $wgLanguageCode ?>" dir="ltr" class="client-nojs">
    <head>
        <meta charset="UTF-8" />
        <title><?php echo $wgSitename;?></title>
        <link rel="shortcut icon" href="/favicon.ico" />
        <link rel="search" type="application/opensearchdescription+xml" href="/opensearch_desc.php" />
        <link rel="EditURI" type="application/rsd+xml" href="/api.php?action=rsd" />
        <link rel="alternate" hreflang="x-default" href="/index.php/%E9%A6%96%E9%A1%B5" />
        <link rel="stylesheet" href="/load.php?debug=false&amp;lang=<?php echo $wgLanguageCode ?>&amp;modules=mediawiki.legacy.commonPrint%2Cshared%7Cmediawiki.skinning.interface%7Cmediawiki.ui.button%7Cskins.vector.styles&amp;only=styles&amp;skin=vector&amp;*" />
        <style>
            a:lang(ar),a:lang(kk-arab),a:lang(mzn),a:lang(ps),a:lang(ur){text-decoration:none}
            .Personal{position:absolute;right:0;top:20;margin-right:20px;margin-top:10px;font-size:12px}
            .footer{width:100%;text-align: center;font-size:12px;margin-top:150px}
            .Personal a{margin-left:2px}
        </style>
        <script src="/load.php?debug=false&amp;lang=<?php echo $wgLanguageCode ?>&amp;modules=startup&amp;only=scripts&amp;skin=vector&amp;*">
        </script>
        <script>
            if (window.mw) {
                mw.loader.load(["mediawiki.page.startup", "mediawiki.legacy.wikibits", "mediawiki.legacy.ajax", "skins.vector.js"]);
            }
        </script>
        <!--[if lt IE 7]>
            <style type="text/css">
                body{behavior:url("/skins/Vector/csshover.min.htc")}
            </style>
        <![endif]-->
    </head>
    <body>
    	<div class="Personal">
    		<?php
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
    			if($_SESSION['wsUserID'] && $_SESSION['wsUserName']){
Contributions
    		?>Hello:<a href="/index.php/User:<?php echo $_SESSION['wsUserName'] ?>"><?php echo $_SESSION['wsUserName'] ?></a> <a href="/index.php/User_talk:<?php echo $_SESSION['wsUserName'] ?>"><?php echo wfMsg('mytalk');?></a> <a href="/index.php/Special:<?php echo wfMsg('Preferences');?>"><?php echo wfMsg('Preferences');?></a> <a href="/index.php/Special:<?php echo wfMsg('Watchlist');?>"><?php echo wfMsg('Watchlist');?></a> <a href="/index.php/Special:Contributions/<?php echo $_SESSION['wsUserName'] ?>"><?php wfMsg('Contributions');?></a> <a href="/index.php?title=Special:<?php echo $_SESSION['UserLogout'] ?>&amp;returnto=Main+Page"><?php echo wfMsg('Logout');?></a> <a href="/"><?php echo $_SESSION['Integral'] ?>:<?php echo $result['integral'];?></a>
    		<?php
    		}else{
    		?>
    		<a href="/index.php?title=Special:UserLogin&returnto=%E9%A6%96%E9%A1%B5&type=signup"><?php echo wfMsg('nologinlink');?></a> | <a href="/index.php?title=Special:UserLogin"><?php echo wfMsg('login'); ?></a>

    		<?php
    		}
    		?>
    	</div>
        <div id="mw-navigation">
        	<table width="100%"><tr><td align="center">
        	<a href="index.php"><img title="<?php echo wfMsg('tooltip-p-logo')?>" src="/resources/assets/wiki.png" class="vx"/></a>
            <form action="/index.php" id="searchform">
                <div id="simpleSearch">
                    <input type="search" name="search" placeholder="<?php echo wfMsg('searchbutton')?>" title="<?php echo wfMsg('searchbutton')?><?php echo $wgSitename;?>[f]"
                    accesskey="f" id="searchInput" />
                    <input type="hidden" value="特殊:<?php echo wfMsg('searchbutton')?>" name="title" />
                    <input type="submit" name="fulltext" value="<?php echo wfMsg('searchbutton')?>" title="<?php echo wfMsg('tooltip-search-fulltext')?>" id="mw-searchButton"
                    class="searchButton mw-fallbackSearchButton" />
                    <input type="submit" name="go" value="前往" title="<?php echo wfMsg('tooltip-search-go')?>"
                    id="searchButton" class="searchButton" />
                </div>
            </form>
        	</td></tr>
        	<tr><td height="50" align="center"><a style="margin-top:20px" href="index.php/">
        		<?php echo wfMsg('tooltip-p-logo');?>
        	</a></td></tr>
        	</table>
        </div>
        <div class="footer">
        	<a href="index.php/<?php echo $wgSitename;?>:<?php echo wfMsg('privacy');?>"><?php echo wfMsg('privacy');?></a> &nbsp; 
        	<a href="index.php/<?php echo $wgSitename;?>:<?php echo wfMsg('about');?>"><?php echo wfMsg('about');?><?php echo $wgSitename;?></a> &nbsp; 
        	<a href="index.php/<?php echo $wgSitename;?>:<?php echo wfMsg('exif-disclaimer');?>"><?php echo wfMsg('exif-disclaimer');?></a>
        </div>
        <style type="text/css">
        	div#simpleSearch{width:500px;}
        	.vx{margin-top:200px}
        </style>
        <script>
            /*<![CDATA[*/
            window.jQuery && jQuery.ready();
            /*]]>*/
        </script>
        <script>
            if (window.mw) {
                mw.loader.state({
                    "site": "ready",
                    "user": "ready",
                    "user.groups": "ready"
                });
            }
        </script>
        <script>
            if (window.mw) {
                mw.loader.load(["mediawiki.action.view.postEdit", "mediawiki.user", "mediawiki.hidpi", "mediawiki.page.ready", "mediawiki.searchSuggest"], null, true);
            }
        </script>
        <script>
            if (window.mw) {
                mw.config.set({
                    "wgBackendResponseTime": 406
                });
            }
        </script>
    </body>

</html>
<?php

?>
