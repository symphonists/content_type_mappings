(function($, Symphony) {
	'use strict';

	$(document).on('ready.ctm', function() {
		$('#ctm-duplicator').symphonyDuplicator({
			orderable: true,
			collapsible: true
		});
	});

})(window.jQuery, window.Symphony);
