(function($) {
	function inuit() {
		new QRCode(document.getElementById("qrcode"), app.submit_url);

		if (app.wallpaper_url) {
			var qrcode = new QRCode("qrcode-wall", {
				text: 'http:'+app.wallpaper_url,
				width: 128,
				height: 128,
				colorDark : "#999",
				colorLight : "#eee",
				correctLevel : QRCode.CorrectLevel.H
			});
		}
	}

	function init() {
		inuit();
	}

	$(function() {
		init();
	});
}) (jQuery);