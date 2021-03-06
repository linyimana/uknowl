<?php
/**
 * Hooks for Title Blacklist
 * @author Victor Vasiliev
 * @copyright © 2007-2010 Victor Vasiliev et al
 * @license GNU General Public License 2.0 or later
 */

/**
 * Hooks for the TitleBlacklist class
 *
 * @ingroup Extensions
 */
class TitleBlacklistHooks {

	/**
	 * getUserPermissionsErrorsExpensive hook
	 *
	 * @param $title Title
	 * @param $user User
	 * @param $action
	 * @param $result
	 * @return bool
	 */
	public static function userCan( $title, $user, $action, &$result ) {
		# Some places check createpage, while others check create.
		# As it stands, upload does createpage, but normalize both
		# to the same action, to stop future similar bugs.
		if ( $action === 'createpage' || $action === 'createtalk' ) {
			$action = 'create';
		}
		if ( $action == 'create' || $action == 'edit' || $action == 'upload' ) {
			$blacklisted = TitleBlacklist::singleton()->userCannot( $title, $user, $action );
			if ( $blacklisted instanceof TitleBlacklistEntry ) {
				$result = array( $blacklisted->getErrorMessage( 'edit' ),
					htmlspecialchars( $blacklisted->getRaw() ),
					$title->getFullText() );
				return false;
			}
		}
		return true;
	}

	/**
	 * Display a notice if a user is only able to create or edit a page
	 * because they have tboverride (or autoconfirmed).
	 *
	 * @param Title $title
	 * @param integer $oldid
	 * @param array &$notices
	 */
	public static function displayBlacklistOverrideNotice( Title $title, $oldid, array &$notices ) {
		$blacklisted = TitleBlacklist::singleton()->isBlacklisted(
			$title,
			$title->exists() ? 'edit' : 'create'
		);
		if ( $blacklisted ) {
			$params = $blacklisted->getParams();
			$msg = wfMessage(
				isset( $params['autoconfirmed'] ) ?
				'titleblacklist-autoconfirmed-warning' :
				'titleblacklist-warning'
			);
			$notices['titleblacklist'] = $msg->rawParams(
				htmlspecialchars( $blacklisted->getRaw() ) )->parseAsBlock();
		}
		return true;
	}

	/**
	 * AbortMove hook
	 *
	 * @param $old Title
	 * @param $nt Title
	 * @param $user User
	 * @param $err
	 * @return bool
	 */
	public static function abortMove( $old, $nt, $user, &$err ) {
		$titleBlacklist = TitleBlacklist::singleton();
		$blacklisted = $titleBlacklist->userCannot( $nt, $user, 'move' );
		if ( !$blacklisted ) {
			$blacklisted = $titleBlacklist->userCannot( $old, $user, 'edit' );
		}
		if ( $blacklisted instanceof TitleBlacklistEntry ) {
			$err = wfMessage( $blacklisted->getErrorMessage( 'move' ),
				$blacklisted->getRaw(),
				$old->getFullText(),
				$nt->getFullText() )->parse();
			return false;
		}
		return true;
	}

	/**
	 * Check whether a user name is acceptable,
	 * and set a message if unacceptable.
	 *
	 * Used by abortNewAccount and centralAuthAutoCreate
	 *
	 * @return bool Acceptable
	 */
	private static function acceptNewUserName( $userName, $permissionsUser, &$err, $override = true, $log = false ) {
		global $wgUser;
		$title = Title::makeTitleSafe( NS_USER, $userName );
		$blacklisted = TitleBlacklist::singleton()->userCannot( $title, $permissionsUser,
			'new-account', $override );
		if ( $blacklisted instanceof TitleBlacklistEntry ) {
			$message = $blacklisted->getErrorMessage( 'new-account' );
			$err = wfMessage( $message, $blacklisted->getRaw(), $userName )->parse();
			if ( $log ) {
				self::logFilterHitUsername( $wgUser, $title, $blacklisted->getRaw() );
			}
			return false;
		}
		return true;
	}

	/**
	 * AbortNewAccount hook
	 *
	 * @param User $user
	 */
	public static function abortNewAccount( $user, &$message ) {
		global $wgUser, $wgRequest;
		$override = $wgRequest->getCheck( 'wpIgnoreTitleBlacklist' );
		return self::acceptNewUserName( $user->getName(), $wgUser, $message, $override, true );
	}

	/**
	 * EditFilter hook
	 *
	 * @param $editor EditPage
	 */
	public static function validateBlacklist( $editor, $text, $section, &$error ) {
		global $wgUser;
		$title = $editor->mTitle;

		if ( $title->getNamespace() == NS_MEDIAWIKI && $title->getDBkey() == 'Titleblacklist' ) {

			$blackList = TitleBlacklist::singleton();
			$bl = $blackList->parseBlacklist( $text, 'page' );
			$ok = $blackList->validate( $bl );
			if ( count( $ok ) == 0 ) {
				return true;
			}

			$errmsg = wfMessage( 'titleblacklist-invalid' )->numParams( count( $ok ) )->text();
			$errlines = '* <code>' . implode( "</code>\n* <code>", array_map( 'wfEscapeWikiText', $ok ) ) . '</code>';
			$error = Html::openElement( 'div', array( 'class' => 'errorbox' ) ) .
				$errmsg .
				"\n" .
				$errlines .
				Html::closeElement( 'div' ) . "\n" .
				Html::element( 'br', array( 'clear' => 'all' ) ) . "\n";

			// $error will be displayed by the edit class
			return true;
		} elseif ( !$section ) {
			# Block redirects to nonexistent blacklisted titles
			$retitle = Title::newFromRedirect( $text );
			if ( $retitle !== null && !$retitle->exists() )  {
				$blacklisted = TitleBlacklist::singleton()->userCannot( $retitle, $wgUser, 'create' );
				if ( $blacklisted instanceof TitleBlacklistEntry ) {
					$error = Html::openElement( 'div', array( 'class' => 'errorbox' ) ) .
						wfMessage( 'titleblacklist-forbidden-edit',
							$blacklisted->getRaw(),
							$retitle->getFullText() )->escaped() .
						Html::closeElement( 'div' ) . "\n" .
						Html::element( 'br', array( 'clear' => 'all' ) ) . "\n";
				}
			}

			return true;
		}
		return true;
	}

	/**
	 * ArticleSaveComplete hook
	 *
	 * @param Article $article
	 */
	public static function clearBlacklist( &$article, &$user,
		$text, $summary, $isminor, $iswatch, $section )
	{
		$title = $article->getTitle();
		if ( $title->getNamespace() == NS_MEDIAWIKI && $title->getDBkey() == 'Titleblacklist' ) {
			TitleBlacklist::singleton()->invalidate();
		}
		return true;
	}

	/** UserCreateForm hook based on the one from AntiSpoof extension */
	public static function addOverrideCheckbox( &$template ) {
		global $wgRequest, $wgUser;

		if ( TitleBlacklist::userCanOverride( $wgUser, 'new-account' ) ) {
			$template->addInputItem( 'wpIgnoreTitleBlacklist',
				$wgRequest->getCheck( 'wpIgnoreTitleBlacklist' ),
				'checkbox', 'titleblacklist-override' );
		}
		return true;
	}

	/**
	 * Logs the filter username hit to Special:Log if
	 * $wgTitleBlacklistLogHits is enabled.
	 *
	 * @param User $user
	 * @param Title $title
	 * @param string $entry
	 */
	public static function logFilterHitUsername( $user, $title, $entry ) {
		global $wgTitleBlacklistLogHits;
		if ( $wgTitleBlacklistLogHits ) {
			$logEntry = new ManualLogEntry( 'titleblacklist', 'hit-username' );
			$logEntry->setPerformer( $user );
			$logEntry->setTarget( $title );
			$logEntry->setParameters( array(
				'4::entry' => $entry,
			) );
			$logid = $logEntry->insert();
			$logEntry->publish( $logid );
		}
	}
}
