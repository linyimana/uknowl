<?php
# MediaWiki Poem extension v1.1

if( !defined( 'MEDIAWIKI' ) ) die( "You can't run this directly." );

$extDir = dirname( __FILE__ ) . '/' ;
global $wgExtensionMessagesFiles, $wgAutoloadClasses;
$wgExtensionMessagesFiles['Integral'] = $extDir . 'Integral.i18n.php';
$wgAutoloadClasses['Integral'] = $extDir . 'Integral.body.php';
	
# Register our methods with the appropriate hook functions.
global $wgExtensionFunctions;
$wgExtensionFunctions[] = 'Integral::registerHooks';
$wgExtensionFunctions[] = 'Integral::Editor';

?>
