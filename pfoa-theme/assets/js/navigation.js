/**
 * Accessible disclosure navigation for the PFOA header.
 *
 * Desktop links remain ordinary links. JavaScript adds explicit disclosure
 * behavior for submenu buttons and the in-flow mobile navigation only.
 */
( function () {
	'use strict';

	var body = document.body;
	var header = document.getElementById( 'masthead' );
	var navigation = document.getElementById( 'site-navigation' );
	var menuToggle = header ? header.querySelector( '.menu-toggle' ) : null;
	var mobileQuery = window.matchMedia( '(max-width: 1319px)' );
	var menuOpen = false;

	if ( ! body || ! header || ! navigation || ! menuToggle ) {
		return;
	}

	body.classList.remove( 'no-js' );
	body.classList.add( 'js' );

	var submenuButtons = Array.prototype.slice.call(
		navigation.querySelectorAll( '.submenu-toggle' )
	);

	var getMenuItem = function ( button ) {
		return button.closest( '.menu-item-has-children' );
	};

	var getSubmenu = function ( button ) {
		var submenuId = button.getAttribute( 'aria-controls' );

		return submenuId ? document.getElementById( submenuId ) : null;
	};

	var setSubmenuState = function ( button, open ) {
		var menuItem = getMenuItem( button );
		var submenu = getSubmenu( button );
		var label = button.querySelector( '.screen-reader-text' );
		var link = menuItem ? menuItem.querySelector( ':scope > a' ) : null;
		var title = link ? link.textContent.trim() : '';
		var openLabel = button.getAttribute( 'data-open-label' ) || ( 'Open submenu for ' + title );
		var closeLabel = button.getAttribute( 'data-close-label' ) || ( 'Close submenu for ' + title );

		button.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		if ( label ) {
			label.textContent = open ? closeLabel : openLabel;
		}

		if ( menuItem ) {
			menuItem.classList.toggle( 'is-open', open );
			menuItem.classList.toggle( 'is-dismissed', ! open );
		}

		if ( submenu && mobileQuery.matches ) {
			submenu.hidden = ! open;
		}
	};

	var closeSubmenu = function ( button, restoreFocus ) {
		setSubmenuState( button, false );

		if ( restoreFocus ) {
			button.focus();
		}
	};

	var closeSubmenus = function () {
		submenuButtons.forEach( function ( button ) {
			setSubmenuState( button, false );
		} );
	};

	var syncSubmenuVisibility = function () {
		submenuButtons.forEach( function ( button ) {
			var submenu = getSubmenu( button );
			var open = button.getAttribute( 'aria-expanded' ) === 'true';

			if ( submenu ) {
				submenu.hidden = mobileQuery.matches && ! open;
			}
		} );
	};

	var setMainMenuState = function ( open, restoreFocus ) {
		menuOpen = open;
		header.classList.toggle( 'is-menu-open', open );
		menuToggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		var toggleLabel = menuToggle.querySelector( '.screen-reader-text' );

		if ( toggleLabel ) {
			toggleLabel.textContent = open ? menuToggle.getAttribute( 'data-close-label' ) : menuToggle.getAttribute( 'data-open-label' );
		}

		if ( mobileQuery.matches ) {
			navigation.hidden = ! open;
		} else {
			navigation.hidden = false;
		}

		if ( ! open ) {
			closeSubmenus();
		}

		syncSubmenuVisibility();

		if ( restoreFocus ) {
			menuToggle.focus();
		}
	};

	var focusFirstMenuLink = function () {
		var firstLink = navigation.querySelector( 'a' );

		if ( firstLink ) {
			firstLink.focus();
		}
	};

	var getDesktopFocusTarget = function ( activeElement ) {
		if ( activeElement === menuToggle ) {
			return navigation.querySelector( '.primary-menu > .menu-item > a' );
		}

		if ( ! navigation.contains( activeElement ) || ! activeElement.closest( '.sub-menu' ) ) {
			return null;
		}

		/* All branches are collapsed below, so the top-level parent is the
		 * nearest control that remains visible after the transition. */
		var topLevelItem = activeElement.closest( '.primary-menu > .menu-item' );

		return topLevelItem ? topLevelItem.querySelector( ':scope > a' ) : navigation.querySelector( '.primary-menu > .menu-item > a' );
	};

	menuToggle.addEventListener( 'click', function () {
		setMainMenuState( ! menuOpen, false );

		if ( menuOpen ) {
			focusFirstMenuLink();
		}
	} );

	submenuButtons.forEach( function ( button ) {
		var menuItem = getMenuItem( button );

		button.addEventListener( 'click', function () {
			var open = button.getAttribute( 'aria-expanded' ) !== 'true';

			setSubmenuState( button, open );
		} );

		if ( ! menuItem ) {
			return;
		}

		menuItem.addEventListener( 'mouseenter', function () {
			if ( ! mobileQuery.matches ) {
				menuItem.classList.remove( 'is-dismissed' );
				setSubmenuState( button, true );
			}
		} );

		menuItem.addEventListener( 'mouseleave', function () {
			if ( ! mobileQuery.matches && ! menuItem.contains( document.activeElement ) ) {
				setSubmenuState( button, false );
			}
		} );

		menuItem.addEventListener( 'focusin', function () {
			if ( ! mobileQuery.matches ) {
				menuItem.classList.remove( 'is-dismissed' );
				setSubmenuState( button, true );
			}
		} );

		menuItem.addEventListener( 'focusout', function ( event ) {
			if ( ! mobileQuery.matches && ! menuItem.contains( event.relatedTarget ) && ! menuItem.matches( ':hover' ) ) {
				setSubmenuState( button, false );
			}
		} );
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! header.contains( event.target ) && mobileQuery.matches && menuOpen ) {
			setMainMenuState( false, false );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key !== 'Escape' ) {
			return;
		}

		var activeSubmenuButton = submenuButtons
			.slice()
			.reverse()
			.find( function ( button ) {
				return button.getAttribute( 'aria-expanded' ) === 'true' && button.contains( document.activeElement );
			} );

		if ( ! activeSubmenuButton ) {
			activeSubmenuButton = submenuButtons
				.slice()
				.reverse()
				.find( function ( button ) {
					var menuItem = getMenuItem( button );

					return button.getAttribute( 'aria-expanded' ) === 'true' && menuItem && menuItem.contains( document.activeElement );
				} );
		}

		if ( activeSubmenuButton ) {
			closeSubmenu( activeSubmenuButton, true );
			return;
		}

		if ( mobileQuery.matches && menuOpen && header.contains( document.activeElement ) ) {
			setMainMenuState( false, true );
			event.preventDefault();
		}
	} );

	var handleViewportChange = function () {
		var activeElement = document.activeElement;
		var focusTarget = null;

		if ( mobileQuery.matches ) {
			if ( navigation.contains( activeElement ) ) {
				focusTarget = menuToggle;
			}
		} else {
			focusTarget = getDesktopFocusTarget( activeElement );
		}

		/* Both modes start a breakpoint transition from a closed, synchronized state. */
		setMainMenuState( false, false );

		if ( focusTarget ) {
			focusTarget.focus();
		}
	};

	if ( mobileQuery.addEventListener ) {
		mobileQuery.addEventListener( 'change', handleViewportChange );
	} else if ( mobileQuery.addListener ) {
		mobileQuery.addListener( handleViewportChange );
	}

	setMainMenuState( false, false );
}() );
