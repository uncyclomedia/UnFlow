<?php

/**
 * Static class to help with creating the
 * Table of Contents above index pages
 */
class UnTOC {
	/**
	 * @return string
	 */
	public static function getHeader() {
		return self::div( 'mw-unflow-toc-header',
			self::div( 'mw-unflow-toc-header-topic', wfMessage( 'unflow-toc-header-topic' )->escaped() )
			. self::div( 'mw-unflow-toc-header-replies', wfMessage( 'unflow-toc-header-replies' )->escaped() )
			. self::div( 'mw-unflow-toc-header-lastedit', wfMessage( 'unflow-toc-header-lastedit' )->escaped() )
		);
	}

	/**
	 * @param stdClass $data
	 * @param UnThread $thread
	 * @param string $topicHtml
	 * @param Language $lang
	 * @return string
	 */
	public static function formatRow( $data, UnThread $thread, $topicHtml, $lang ) {
		$user = User::newFromName( $data->user, false );
		$cleanedHtml = self::cleanUpThingy( $topicHtml );
		$link = Html::rawElement( 'a', array( 'href' => "#{$thread->getId()}" ), $cleanedHtml );
		$html = '<div class="mw-unflow-toc-line">';
		$html .= '<div class="mw-unflow-toc-topic">';
		$html .= self::div( 'mw-unflow-toc-topic-link', $link );
		$html .= self::div( 'mw-unflow-toc-by',
			wfMessage( 'unflow-toc-by' )->rawParams(
				Linker::userLink( $user->getId(), $user->getName() ),
				$lang->formatExpiry( $data->ts )
			)->escaped()
		);
		$html .= '</div>';
		$html .= self::div( 'mw-unflow-toc-replies',
			wfMessage( 'unflow-toc-replies' )->numParams(
				UnThread::countReplies( $thread )
			)->escaped()
		);
		$latest = UnThread::findLatestReply( $thread );
		$html .= '<div class="mw-unflow-toc-latest">';
		$html .= self::div( 'mw-unflow-toc-latest-ts', $lang->formatExpiry( $latest->getTimestamp() ) );
		$user = $latest->getUser(); // @todo check for revdel here.
		$html .= self::div( 'mw-unflow-toc-latest-user',
			wfMessage( 'unflow-toc-by-no-ts' )
				->rawParams( Linker::userLink( $user->getId(), $user->getName() ) )
				->escaped()
		);
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	protected static function div( $cls, $text ) {
		return "<div class=\"$cls\">$text</div>";
	}

	/**
	 * This function is basically a hack because extending the
	 * parser wasn't really possible.
	 * @see Parser::formatHeadings
	 * @param string $html
	 * @return string
	 */
	protected static function cleanUpThingy( $html ) {
		$tocline = preg_replace(
			array( '#<(?!/?(span|sup|sub|i|b)(?: [^>]*)?>).*?' . '>#', '#<(/?(?:span(?: dir="(?:rtl|ltr)")?|sup|sub|i|b))(?: .*?)?' . '>#' ),
			array( '', '<$1>' ),
			$html
		);

		return trim( $tocline );
	}
}