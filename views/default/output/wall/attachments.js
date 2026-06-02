define(function (require) {

	var lib = {
		init: function () {
			var lightbox = require('elgg/lightbox');
			var options = {
				photo: true,
			};
			lightbox.on('.wall-popup-link', options, false);
		}
	};

	return lib;
});
