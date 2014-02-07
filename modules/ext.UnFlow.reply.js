( function ( mw, $ ) {
	function getHtml() {
		return '<textarea class="mw-unpost-text"></textarea>' +
			'<button class="mw-unpost-button-reply">' +
			mw.message( 'unflow-submit-reply' ).text() +
			'</button>';
	}

	$( function () {
		'use strict';
		$( '.mw-unflow-reply-link' ).click( function( e ) {
			e.preventDefault();
			var $self = $( this );
			$self.closest( '.mw-unpost-comment-container' ).after(
				$( '<div class="mw-unpost-reply">' )
					.html( getHtml() )
					.attr( 'data-thread-id', $self.attr( 'data-thread-id' ) )
					.attr( 'data-post-id', $self.attr( 'data-post-id' ) )
			);

			$( '.mw-unpost-button-reply' ).click( function( e ) {
				e.preventDefault();
				var $this = $( this );

				( new mw.Api() ).post({
					action: 'newreply',
					threadId: $this.closest( '.mw-unpost-reply' ).attr( 'data-thread-id' ),
					postId: $this.closest( '.mw-unpost-reply' ).attr( 'data-post-id' ),
					text: $( '.mw-unpost-text' ).val(), // @fixme fix this
					token: mw.user.tokens.get( 'editToken' )
				} )
					.done( function( data ) {
						// @todo something here...
					} );
			});

		});
	});

}( mediaWiki, jQuery ) );
