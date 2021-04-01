/**
 * WordPress dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import PatternPreview from './components/pattern-preview';
import CategoryContextBar from './components/category-context-bar';

// Load the preview into any awaiting preview container.
const previewContainers = document.querySelectorAll( '.pattern-preview__container' );
for ( let i = 0; i < previewContainers.length; i++ ) {
	const container = previewContainers[ i ];
	const blockContent = JSON.parse( decodeURIComponent( container.innerText ) );
	// Use `wp.blocks.parse` to convert HTML to block objects (for use in editor), if needed.

	render( <>
    {/* <PatternPreview blockContent={ blockContent } /> */}
    <CategoryContextBar actionsTitle="Something" actions={ [ { label: 'Url', href: '#url'},{ label: 'Thin', href: '#nothing'} ]}>
        This is the contexzt
    </CategoryContextBar>
    </>, container, () => {
		// This callback is called after the render to unhide the container.
		container.hidden = false;
	} );
}
