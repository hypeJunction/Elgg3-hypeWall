import lightbox from 'elgg/lightbox';

const lib = {
	init: function () {
		const options = {
			photo: true,
		};
		lightbox.bind('.wall-popup-link', options, false);
	}
};

export default lib;
