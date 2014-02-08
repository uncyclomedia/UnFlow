( function ( mw, $ ) {
	function getHtml( postId ) {
		return '<textarea class="mw-unpost-text"></textarea>' +
			'<button class="mw-unpost-button-reply">' +
			mw.message( 'unflow-submit-reply' ).text() +
			'</button>' + '<button class="mw-unpost-button-cancel">' +
			mw.message( 'unflow-submit-cancel' ).text() +
			'</button>';
	}

	$( function () {
		'use strict';
		$( '.mw-unflow-reply-link' ).click( function( e ) {
			e.preventDefault();
			var $self = $( this ),
				postId = $self.attr( 'data-post-id' ),
				threadId = $self.attr( 'data-thread-id'),
				$elem = $( '#reply-' + postId );

			if ( !$elem.length ) {
				$self.closest( '.mw-unpost-comment-container' ).after(
					$( '<div id="reply-' + postId + '" class="mw-unpost-reply">' )
						.html( getHtml( postId ) )
						.attr( 'data-thread-id', threadId )
						.attr( 'data-post-id', postId )
				);
			} else {
				$elem.show();
			}

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

			$( '.mw-unpost-button-cancel').click( function( e ) {
				e.preventDefault();
				$( this ).closest( '.mw-unpost-reply' ).hide();
			})

		});
	});

}( mediaWiki, jQuery ) );
