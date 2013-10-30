$(function () {

	if ((window.history && history.pushState && window.history.replaceState && !navigator.userAgent.match(/((iPod|iPhone|iPad).+\bOS\s+[1-4]|WebApps\/.+CFNetwork)/))) {
		$.nette.ext('init').linkSelector = 'a';
	}

	$.nette.ext('spinner', {
		start: function () {
			$('html').addClass('wait');
		},
		complete: function () {
			$('html').removeClass('wait');
			$('html,body').animate({scrollTop: $(".nav").offset().top}, 100);
		}
	});

	$.nette.init();

	$('body').on('click', '[data-confirm]', function (e) {
		var question = $(this).data('confirm');
		if (!confirm(question)) {
			e.stopImmediatePropagation();
			e.preventDefault();
		}
	});

	$('#qr').qrcode({
		text: document.URL,
		radius: 0.5,
		size: 107
	});

	$("#outline, #outline2").fracs("outline", {
		crop: true,
		styles: [
			{selector: "p", fillStyle: "rgb(230,230,230)"},
			{selector: "pre", fillStyle: "rgb(200,200,200)"},
			{selector: "a,h1,h2,h3,h4,h5,h6", fillStyle: "rgb(104,169,255)"},
			{selector: "canvas", fillStyle: "rgb(108,196,46)"},
			{selector: "blockquote,.thumbnail,#disqus_thread", fillStyle: "rgb(221,75,57)"},
			{selector: "table", fillStyle: "rgb(200,200,30)"}

		],
		viewportStyle: {fillStyle: "rgba(104,169,255,0.2)"},
		viewportDragStyle: {fillStyle: "rgba(104,169,255,0.5)"}
	});

	var fixAffix = function () {
		return $('#bottom').outerHeight(true) + $('.footer').outerHeight(true) + 40;
	}
	$('#outline').affix({
		offset: {
			top: 351,
			bottom: function () {
				return (this.bottom = fixAffix);
			}
		}
	})
	$(window).scroll(fixAffix);

	$('#outline2').affix({
		offset: {
			top: 264,
			bottom: 100
		}
	})

	var disqus_div = $("#disqus_thread");
	if (disqus_div.size() > 0) {
		var ds_loaded = false,
			top = $('.load_disqus').offset().top,
			disqus_data = disqus_div.data(),
			check = function () {
				if (!ds_loaded && $(window).scrollTop() + $(window).height() > top) {
					ds_loaded = true;
					for (var key in disqus_data) {
						if (key.substr(0, 6) == 'disqus') {
							window['disqus_' + key.replace('disqus', '').toLowerCase()] = disqus_data[key];
						}
					}
					var dsq = document.createElement('script');
					dsq.type = 'text/javascript';
					dsq.async = true;
					dsq.src = 'http://' + window.disqus_shortname + '.disqus.com/embed.js';
					(document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
				}
			};
		$(window).scroll(check);
		check();
	}

	// generuje URL na zaklade zadavaneho titulku
	$('input[data-slug-to]').keyup(function () {
		var slugId = $(this).data('slug-to');
		var val = $(this).val();
		$('#' + slugId).val(make_url(val));
	});

});

var nodiac = { 'á': 'a', 'č': 'c', 'ď': 'd', 'é': 'e', 'ě': 'e', 'í': 'i', 'ň': 'n', 'ó': 'o', 'ř': 'r', 'š': 's', 'ť': 't', 'ú': 'u', 'ů': 'u', 'ý': 'y', 'ž': 'z' };
/** Vytvoření přátelského URL
 * @param string řetězec, ze kterého se má vytvořit URL
 * @return string řetězec obsahující pouze čísla, znaky bez diakritiky, podtržítko a pomlčku
 * @copyright Jakub Vrána, http://php.vrana.cz/
 */
function make_url(s) {
	s = s.toLowerCase();
	var s2 = '';
	for (var i = 0; i < s.length; i++) {
		s2 += (typeof nodiac[s.charAt(i)] != 'undefined' ? nodiac[s.charAt(i)] : s.charAt(i));
	}
	return s2.replace(/[^a-z0-9_]+/g, '-').replace(/^-|-$/g, '');
}