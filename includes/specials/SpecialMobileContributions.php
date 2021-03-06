<?php
// FIXME: On Special:Contributions add ability to filter a la desktop
class SpecialMobileContributions extends SpecialMobileHistory {
	// Note we do not redirect to Special:History/$par to allow the parameter to be used for usernames
	protected $specialPageName = 'Contributions';
	/**  @var User */
	protected $user;

	public function executeWhenAvailable( $par = '' ) {
		wfProfileIn( __METHOD__ );
		$out = $this->getOutput();
		if ( $par ) {
			// enter article history view
			$this->user = User::newFromName( $par );
			$this->renderHeaderBar( $this->msg( 'mobile-frontend-contribution-summary',
				$this->user->getName() ), true );
		}
		$res = $this->doQuery();
		$this->showHistory( $res );
		wfProfileOut( __METHOD__ );
	}

	protected function getQueryConditions() {
		if ( $this->user ) {
			$conds = array(
				'rev_user' => $this->user->getID(),
			);
		} else {
			$conds = array();
		}
		return $conds;
	}

	protected function renderFeedItemHtml( $ts, $diffLink ='', $username = '', $comment = '', $title = false, $isAnon=false ) {
		// Stop username from being rendered
		$username = False;
		parent::renderFeedItemHtml( $ts, $diffLink, $username, $comment, $title );
	}

}
