/**
 * Minimal Markdown → HTML for a single imported article.
 *
 * This is intentionally small: one file becomes one draft.
 *
 * @param markdown
 */
export function markdownToHtml( markdown: string ): string {
	const lines = markdown.replace( /\r\n/g, '\n' ).split( '\n' );
	const html: string[] = [];
	let inCode = false;
	let code: string[] = [];
	let list: string[] = [];

	const flushList = () => {
		if ( ! list.length ) {
			return;
		}

		html.push( `<ul>${ list.join( '' ) }</ul>` );
		list = [];
	};

	const flushCode = () => {
		if ( ! inCode ) {
			return;
		}

		html.push(
			`<pre><code>${ escapeHtml( code.join( '\n' ) ) }</code></pre>`
		);
		code = [];
		inCode = false;
	};

	lines.forEach( ( line ) => {
		if ( line.startsWith( '```' ) ) {
			if ( inCode ) {
				flushCode();
			} else {
				flushList();
				inCode = true;
			}
			return;
		}

		if ( inCode ) {
			code.push( line );
			return;
		}

		const listMatch = line.match( /^\s*[-*+]\s+(.+)$/ );

		if ( listMatch ) {
			list.push( `<li>${ inline( listMatch[ 1 ] ) }</li>` );
			return;
		}

		flushList();

		if ( ! line.trim() ) {
			return;
		}

		const heading = line.match( /^(#{1,4})\s+(.+)$/ );

		if ( heading ) {
			const level = heading[ 1 ].length;
			html.push( `<h${ level }>${ inline( heading[ 2 ] ) }</h${ level }>` );
			return;
		}

		html.push( `<p>${ inline( line ) }</p>` );
	} );

	flushList();
	flushCode();

	return html.join( '\n' );
}

export function titleFromMarkdown( markdown: string, fallback: string ): string {
	const heading = markdown.match( /^\s*#\s+(.+)$/m );

	if ( heading ) {
		return heading[ 1 ].trim();
	}

	return fallback.replace( /\.md$/i, '' ).replace( /[-_]+/g, ' ' ).trim();
}

export function bodyFromMarkdown( markdown: string ): string {
	return markdown.replace( /^\s*#\s+.+\n+/, '' );
}

function inline( value: string ): string {
	return escapeHtml( value )
		.replace(
			/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
			'<a href="$2">$1</a>'
		)
		.replace( /`([^`]+)`/g, '<code>$1</code>' )
		.replace( /\*\*([^*]+)\*\*/g, '<strong>$1</strong>' )
		.replace( /\*([^*]+)\*/g, '<em>$1</em>' );
}

function escapeHtml( value: string ): string {
	return value
		.replace( /&/g, '&amp;' )
		.replace( /</g, '&lt;' )
		.replace( />/g, '&gt;' )
		.replace( /"/g, '&quot;' );
}
