/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { DropdownMenu, MenuGroup, MenuItem } from '@wordpress/components';
import { select } from '@wordpress/data';
import { store } from '@wordpress/editor';
import { render, useEffect, useState } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

/**
 * Internal dependencies
 */
import UnitControl from '../components/unit-control';
import usePostData from '../hooks/use-post-data';

/**
 * Module Constants
 */
const CONTAINER_ID = 'pattern-viewport-container';
const META_KEY = 'wpop_viewport_width';
const WIDTH_DEFAULTS = [
	{
		label: 'Narrow (320px)',
		value: 320,
	},
	{
		label: 'Default (800px)',
		value: 800,
	},
	{
		label: 'Full (1200px)',
		value: 1200,
	},
];

/**
 * Removes the button container if it exists
 */
const maybeRemoveContainer = () => {
	const container = document.getElementById( CONTAINER_ID );

	if ( container && container.parentElement ) {
		container.parentElement.removeChild( container );
	}
};

/**
 * Inserts an empty container so we can bind our ReactDOM node.
 *
 * @param {external:Node} btnDomRef
 */
const insertContainer = ( btnDomRef ) => {
	const container = document.createElement( 'div' );
	container.id = CONTAINER_ID;

	btnDomRef.parentElement.insertBefore( container, btnDomRef );
};

/**
 * Insert an html element to the right.
 *
 * @param {external:Node} newNode Element to be added
 */
export const insertButton = ( newNode ) => {
	// The Gutenberg Publish Button
	const btnDomRef = document.querySelector( '.editor-post-publish-button__button' );

	if ( ! btnDomRef ) {
		return;
	}

	// We may re-insert the same button if it state's changes
	maybeRemoveContainer();

	insertContainer( btnDomRef );

	render( newNode, document.getElementById( CONTAINER_ID ) );
};

/**
 * Updates the width of the DOM element surrounded the patterns.
 *
 * @param {string} newValue An integer
 */
const updateElementWidth = ( newValue ) => {
	const page = document.querySelector( '.is-root-container' );
	page.style.maxWidth = `${ newValue }px`;
};

const ViewportHeaderControl = () => {
	const [ meta, setMeta ] = usePostData( 'meta' );
	const [ viewportWidth, setViewport ] = useState( meta[ META_KEY ] );

	/**
	 * Updates the setViewport property which rebinds the control to the header.
	 */
	const updateControlWithEditedData = () => {
		// Because this context is bound when we insert the button,
		// We need to retrieve the value from the global state or we will get the original, out of date value
		const editedMeta = select( store ).getEditedPostAttribute( 'meta' );

		setViewport( editedMeta[ META_KEY ] );
	};

	useEffect( () => {
		updateElementWidth( viewportWidth );
	}, [] );

	useEffect( () => {
		insertButton(
			<DropdownMenu
				className="viewport-header-control"
				icon={ null }
				text={ sprintf(
					/* translators: %s viewport width as css, ie: 100% */
					__( `Width (%spx)`, 'wporg-patterns' ),
					viewportWidth
				) }
				popoverProps={ {
					onClose: updateControlWithEditedData,
					onFocusOutside: updateControlWithEditedData,
				} }
				label={ __( 'Select a viewport width', 'wporg-patterns' ) }
			>
				{ () => (
					<>
						<MenuGroup label={ __( 'Viewport Width', 'wporg-patterns' ) }>
							<p className="viewport-header-control__copy">
								{ __(
									'This is used when displaying a preview of your pattern.',
									'wporg-patterns'
								) }
							</p>
							<UnitControl
								label={ __( 'Viewport Width', 'wporg-patterns' ) }
								onChange={ ( newValue ) => {
									setMeta( {
										...meta,
										[ META_KEY ]: newValue,
									} );
									updateElementWidth( newValue );
								} }
								value={ viewportWidth }
							/>
						</MenuGroup>
						<MenuGroup>
							{ WIDTH_DEFAULTS.map( ( i ) => {
								const isActive = i.value === viewportWidth;

								return (
									<MenuItem
										key={ i.value }
										icon={ isActive ? 'yes' : '' }
										isSelected={ isActive }
										onClick={ () => {
											setMeta( {
												...meta,
												[ META_KEY ]: i.value,
											} );
											updateElementWidth( i.value );
											updateControlWithEditedData();
										} }
									>
										{ i.label }
									</MenuItem>
								);
							} ) }
						</MenuGroup>
					</>
				) }
			</DropdownMenu>
		);
	}, [ viewportWidth ] );

	return null;
};

registerPlugin( 'viewport-header-control', {
	render: ViewportHeaderControl,
} );
