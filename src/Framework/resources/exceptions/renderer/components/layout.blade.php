@use('MVPS\Lumis\Framework\Exceptions\Renderer\Renderer')
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta
			name="viewport"
			content="width=device-width, initial-scale=1"
		/>

		<title>{{ config('app.name', 'Lumis') }}</title>

		<link rel="icon" type="image/png"
			href="data:image/png;base64,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" />

		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

		{!! Renderer::css() !!}

		<style type="text/css">
			@foreach ($exception->frames() as $frame)
				#frame-{{ $loop->index }} .hljs-ln-line[data-line-number='{{ $frame->line() }}'] {
					background-color: rgba(242, 95, 95, 0.4);
				}
			@endforeach
		</style>
	</head>
	<body class="bg-gray-200/80 font-sans antialiased dark:bg-gray-950/95">
		{{ $slot }}

		{!! Renderer::js() !!}

		<script type="text/javascript">
			!function(r,o){"use strict";var e,i="hljs-ln",l="hljs-ln-line",h="hljs-ln-code",s="hljs-ln-numbers",c="hljs-ln-n",m="data-line-number",a=/\r\n|\r|\n/g;function u(e){for(var n=e.toString(),t=e.anchorNode;"TD"!==t.nodeName;)t=t.parentNode;for(var r=e.focusNode;"TD"!==r.nodeName;)r=r.parentNode;var o=parseInt(t.dataset.lineNumber),a=parseInt(r.dataset.lineNumber);if(o==a)return n;var i,l=t.textContent,s=r.textContent;for(a<o&&(i=o,o=a,a=i,i=l,l=s,s=i);0!==n.indexOf(l);)l=l.slice(1);for(;-1===n.lastIndexOf(s);)s=s.slice(0,-1);for(var c=l,u=function(e){for(var n=e;"TABLE"!==n.nodeName;)n=n.parentNode;return n}(t),d=o+1;d<a;++d){var f=p('.{0}[{1}="{2}"]',[h,m,d]);c+="\n"+u.querySelector(f).textContent}return c+="\n"+s}function n(e){try{var n=o.querySelectorAll("code.hljs,code.nohighlight");for(var t in n)n.hasOwnProperty(t)&&(n[t].classList.contains("nohljsln")||d(n[t],e))}catch(e){r.console.error("LineNumbers error: ",e)}}function d(e,n){"object"==typeof e&&r.setTimeout(function(){e.innerHTML=f(e,n)},0)}function f(e,n){var t,r,o=(t=e,{singleLine:function(e){return!!e.singleLine&&e.singleLine}(r=(r=n)||{}),startFrom:function(e,n){var t=1;isFinite(n.startFrom)&&(t=n.startFrom);var r=function(e,n){return e.hasAttribute(n)?e.getAttribute(n):null}(e,"data-ln-start-from");return null!==r&&(t=function(e,n){if(!e)return n;var t=Number(e);return isFinite(t)?t:n}(r,1)),t}(t,r)});return function e(n){var t=n.childNodes;for(var r in t){var o;t.hasOwnProperty(r)&&(o=t[r],0<(o.textContent.trim().match(a)||[]).length&&(0<o.childNodes.length?e(o):v(o.parentNode)))}}(e),function(e,n){var t=g(e);""===t[t.length-1].trim()&&t.pop();if(1<t.length||n.singleLine){for(var r="",o=0,a=t.length;o<a;o++)r+=p('<tr><td class="{0} {1}" {3}="{5}"><div class="{2}" {3}="{5}"></div></td><td class="{0} {4}" {3}="{5}">{6}</td></tr>',[l,s,c,m,h,o+n.startFrom,0<t[o].length?t[o]:" "]);return p('<table class="{0}">{1}</table>',[i,r])}return e}(e.innerHTML,o)}function v(e){var n=e.className;if(/hljs-/.test(n)){for(var t=g(e.innerHTML),r=0,o="";r<t.length;r++){o+=p('<span class="{0}">{1}</span>\n',[n,0<t[r].length?t[r]:" "])}e.innerHTML=o.trim()}}function g(e){return 0===e.length?[]:e.split(a)}function p(e,t){return e.replace(/\{(\d+)\}/g,function(e,n){return void 0!==t[n]?t[n]:e})}r.hljs?(r.hljs.initLineNumbersOnLoad=function(e){"interactive"===o.readyState||"complete"===o.readyState?n(e):r.addEventListener("DOMContentLoaded",function(){n(e)})},r.hljs.lineNumbersBlock=d,r.hljs.lineNumbersValue=function(e,n){if("string"!=typeof e)return;var t=document.createElement("code");return t.innerHTML=e,f(t,n)},(e=o.createElement("style")).type="text/css",e.innerHTML=p(".{0}{border-collapse:collapse}.{0} td{padding:0}.{1}:before{content:attr({2})}",[i,c,m]),o.getElementsByTagName("head")[0].appendChild(e)):r.console.error("highlight.js not detected!"),document.addEventListener("copy",function(e){var n,t=window.getSelection();!function(e){for(var n=e;n;){if(n.className&&-1!==n.className.indexOf("hljs-ln-code"))return 1;n=n.parentNode}}(t.anchorNode)||(n=-1!==window.navigator.userAgent.indexOf("Edge")?u(t):t.toString(),e.clipboardData.setData("text/plain",n),e.preventDefault())})}(window,document);

			hljs.initLineNumbersOnLoad()

			window.addEventListener('load', function() {
				document.querySelectorAll('.renderer').forEach(function(element, index) {
					if (index > 0) {
						element.remove();
					}
				});

				document.querySelector('.default-highlightable-code').style.display = 'block';

				document.querySelectorAll('.highlightable-code').forEach(function(element) {
					element.style.display = 'block';
				})
			});
		</script>
	</body>
</html>
