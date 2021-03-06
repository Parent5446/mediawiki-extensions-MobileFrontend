/*jshint unused:vars */
( function( M, $ ) {

var View = M.require( 'view' ),
	Overlay = View.extend( {
		defaults: {
			closeMsg: mw.msg( 'mobile-frontend-overlay-escape' )
		},
		template: M.template.get( 'overlay' ),
		className: 'mw-mf-overlay',
		closeOnBack: false,
		closeOnContentTap: false,
		fullScreen: true,
		// use '#mw-mf-viewport' rather than 'body' - for some reasons this has
		// odd consequences on Opera Mobile (see bug 52361)
		appendTo: '#mw-mf-viewport',
		initialize: function( options ) {
			options = options || {};
			this.parent = options.parent;
			this.isOpened = false;
			this._super( options );
		},
		postRender: function() {
			var self = this;
			// FIXME change when micro.tap.js in stable
			this.$( '.cancel, .confirm' ).on( M.tapEvent( 'click' ), function( ev ) {
				ev.preventDefault();
				ev.stopPropagation();
				if ( self.closeOnBack ) {
					window.history.back();
				} else {
					self.hide();
				}
			} );
			// stop clicks in the overlay from propagating to the page
			// (prevents non-fullscreen overlays from being closed when they're tapped)
			this.$el.on( M.tapEvent( 'click' ), function( ev ) {
				ev.stopPropagation();
			} );
		},
		show: function() {
			var self = this;

			function hideOnRoute() {
				M.router.one( 'route', function( ev ) {
					if ( !self.hide() ) {
						ev.preventDefault();
						hideOnRoute();
					}
				} );
			}

			if ( this.closeOnBack ) {
				hideOnRoute();
			}

			// FIXME: prevent zooming within overlays but don't break the rendering!
			// M.lockViewport();
			if ( this.parent ) {
				this.parent.hide( true );
			}

			this.$el.appendTo( this.appendTo );
			this.scrollTop = document.body.scrollTop;

			if ( this.fullScreen ) {
				$( 'html' ).addClass( 'overlay-enabled' );
				// skip the URL bar if possible
				window.scrollTo( 0, 1 );
			}

			if ( this.closeOnContentTap ) {
				$( '#mw-mf-page-center' ).one( M.tapEvent( 'click' ), $.proxy( this, 'hide' ) );
			}

			$( 'body' ).removeClass( 'navigation-enabled' );
		},
		/**
		 * Detach the overlay from the current view
		 *
		 * @param {boolean} force: Whether the overlay should be closed regardless of state (see PhotoUploadProgress)
		 * @return {boolean}: Whether the overlay was successfully hidden or not
		 */
		hide: function( force ) {
			// FIXME: allow zooming outside the overlay again
			// M.unlockViewport();
			this.$el.detach();
			if ( this.parent ) {
				this.parent.show();
			} else if ( this.fullScreen ) {
				$( 'html' ).removeClass( 'overlay-enabled' );
				// return to last known scroll position
				window.scrollTo( document.body.scrollLeft, this.scrollTop );
			}
			return true;
		}
	} );

M.define( 'Overlay', Overlay );

}( mw.mobileFrontend, jQuery ) );
