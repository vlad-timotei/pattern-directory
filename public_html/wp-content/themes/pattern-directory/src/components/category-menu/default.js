/**
 * External dependencies
 */
import { useEffect, useRef } from '@wordpress/element';
import { ifViewportMatches } from '@wordpress/viewport';
import { getPath } from '@wordpress/url';

const updateIndicatorLocation = ( container, { top, left, width, height } ) => {
	if ( ! container ) {
		return;
	}

	container.style.backgroundPositionX = `${ left }px`;
	container.style.backgroundSize = `${ width }px ${ top + height }px`;
};

/**
 * Removes the last slash from a string.
 *
 * @param {string} str
 * @return {string}
 */
const stripTrailingSlash = ( str ) => {
	return str.replace( /\/$/, '' );
};

/**
 * Compares the current path with the category link url.
 *
 * @param {string} path
 * @param {string} linkUrl
 * @return {boolean}
 */
const pathMatches = ( path, linkUrl ) => {
	return path === getPath( linkUrl );
};

/**
 * Returns true if the current path matches the basename.
 *
 * @param {string} path
 * @param {string} basename
 * @return {boolean}
 */
const isRoot = ( path, basename ) => {
	// If the path is undefined or an empty string, assume it's the root.
	if ( ! path || path.length < 1 ) {
		return true;
	}

	return stripTrailingSlash( path ) === stripTrailingSlash( basename );
};

const DefaultMenu = ( { path, basename, options, onClick, isLoading } ) => {
	const containerRef = useRef( null );
	const activeRef = useRef( null );

	useEffect( () => {
		if ( ! containerRef || ! containerRef.current || ! activeRef || ! activeRef.current ) {
			return;
		}

		updateIndicatorLocation( containerRef.current, {
			top: activeRef.current.offsetTop,
			left: activeRef.current.offsetLeft,
			width: activeRef.current.offsetWidth,
			height: activeRef.current.offsetHeight,
		} );
	} );

	if ( ! isLoading && ! options.length ) {
		return null;
	}

	return (
		<ul className={ `category-menu ${ isLoading ? 'category-menu--is-loading' : '' } ` } ref={ containerRef }>
			{ options.map( ( option, idx ) => {
				let active = pathMatches( path, option.value );

				// We want to choose the first item if we are at the root.
				// We can't rely on the path of the first item to match the project's root, so use the index.
				if ( isRoot( path, basename ) && idx === 0 ) {
					active = true;
				}

				return (
					<li key={ option.value }>
						<a
							className={ active ? 'category-menu--is-active' : '' }
							href={ option.value }
							ref={ active ? activeRef : null }
							onClick={ ( { target } ) => onClick( target.hash ) }
						>
							{ option.label }
						</a>
					</li>
				);
			} ) }
		</ul>
	);
};

// Will only render if the viewport is >= medium
export default ifViewportMatches( '>= medium' )( DefaultMenu );
