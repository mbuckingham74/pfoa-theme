/* Shared footer only. The public address and directions work without JavaScript. */
(function () {
	'use strict';
	var config = window.pfoaFooterMap;
	var root = document.querySelector('[data-pfoa-footer-map]');
	if (!root || !config || !root.dataset.token) {
		return;
	}
	var container = root.querySelector('.pfoa-footer-map__canvas');
	var status = root.querySelector('.pfoa-footer-map__status');
	var map;
	var script;
	var deadline;
	var finished = false;
	root.dataset.mapState = 'waiting';

	function fail() {
		if (finished) {
			return;
		}
		finished = true;
		clearTimeout(deadline);
		if (script) {
			script.onload = script.onerror = null;
		}
		if (map) {
			map.remove();
		}
		container.hidden = true;
		root.dataset.mapState = 'failed';
		status.textContent = config.unavailable;
		status.hidden = false;
	}

	function initialize() {
		if (finished) {
			return;
		}
		try {
			if (!window.mapboxgl || !window.mapboxgl.supported()) {
				fail();
				return;
			}
			var location = [Number(root.dataset.longitude), Number(root.dataset.latitude)];
			map = new window.mapboxgl.Map({
				container: container,
				accessToken: root.dataset.token,
				style: 'mapbox://styles/mapbox/streets-v12',
				center: location,
				zoom: 13,
				scrollZoom: false,
				cooperativeGestures: true,
				dragRotate: false,
				pitchWithRotate: false,
				touchPitch: false,
				attributionControl: false
			});
			map.touchZoomRotate.disableRotation();
			map.keyboard.disableRotation();
			map.getCanvas().setAttribute('aria-label', config.mapLabel);
			map.addControl(new window.mapboxgl.NavigationControl({ showCompass: false }), 'top-right');
			map.addControl(new window.mapboxgl.AttributionControl({ compact: true }), 'bottom-right');

			var details = root.querySelector('.pfoa-footer-map__details').cloneNode(true);
			details.className = 'pfoa-footer-map__popup';
			var popup = new window.mapboxgl.Popup({ anchor: 'bottom', offset: 25, maxWidth: '210px' }).setDOMContent(details);
			var marker = new window.mapboxgl.Marker({ color: '#002894' })
				.setLngLat(location).setPopup(popup).addTo(map);
			marker.getElement().setAttribute('aria-label', config.markerLabel);
			popup.on('open', function () {
				map.easeTo({ center: location, offset: [0, 65], duration: 200 });
			});
			map.once('load', function () {
				if (!finished) {
					finished = true;
					clearTimeout(deadline);
					root.dataset.mapState = 'ready';
				}
			});
			map.on('error', function () {
				// Keep a usable address if authentication, WebGL, or map loading fails.
				if (!finished) {
					fail();
				}
			});
		} catch (error) {
			fail();
		}
	}

	function start() {
		root.dataset.mapState = 'loading';
		container.hidden = false;
		deadline = window.setTimeout(fail, 20000);
		if (window.mapboxgl) {
			initialize();
			return;
		}
		script = document.createElement('script');
		script.src = config.scriptUrl;
		script.async = true;
		script.onload = initialize;
		script.onerror = fail;
		document.head.appendChild(script);
	}

	if ('IntersectionObserver' in window) {
		var observer = new IntersectionObserver(function (entries) {
			if (entries.some(function (entry) { return entry.isIntersecting; })) {
				observer.disconnect();
				start();
			}
		}, { rootMargin: '300px 0px' });
		observer.observe(root);
	} else {
		// Older browsers retain the address until the footer is actually nearby.
		function checkPosition() {
			if (root.getBoundingClientRect().top <= window.innerHeight + 300) {
				window.removeEventListener('scroll', checkPosition);
				window.removeEventListener('resize', checkPosition);
				start();
			}
		}
		window.addEventListener('scroll', checkPosition, { passive: true });
		window.addEventListener('resize', checkPosition);
		checkPosition();
	}
}());
