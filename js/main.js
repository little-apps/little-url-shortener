jQuery.noConflict();

jQuery(document).ready(function($) {
	if (window.screen.width <= 1366) {
		// Make signupbox visible by having it up against the side
		$("#signinbox").css("right", "0px");
	}
	
	$("#topmenu > #nav > #login > #signin > a").click(function(e) {
		if ($("#registerbox").is(":visible")) {
			$("#registerbox").slideUp(400, function() { $("#topmenu > #nav > #login > #register").removeClass("selected"); });
		}
		
		if ($("#topmenu > #nav > #box > ul").is(":visible")) {
			$("#topmenu > #nav > #box > ul").slideUp(400, function() { $("#topmenu > #nav > #box").removeClass("selected"); });
		}
		
		if ($("#signinbox").is(":visible")) {
			$("#signinbox").slideUp(400, function() { $("#topmenu > #nav > #login > #signin").removeClass("selected") });
		} else {
			$(this).parent().addClass("selected");
			$("#signinbox").slideDown();
		}
		
		e.preventDefault();
	});
	
	$("#topmenu > #nav > #login > #register > a").click(function(e) {
		if ($("#signinbox").is(":visible")) {
			$("#signinbox").slideUp(400, function() { $("#topmenu > #nav > #login > #signin").removeClass("selected"); });
		}
		
		if ($("#topmenu > #nav > #box > ul").is(":visible")) {
			$("#topmenu > #nav > #box > ul").slideUp(400, function() { $("#topmenu > #nav > #box").removeClass("selected"); });
		}
		
		if ($("#registerbox").is(":visible")) {
			$("#registerbox").slideUp(400, function() { $("#topmenu > #nav > #login > #register").removeClass("selected") });
		} else {
			$(this).parent().addClass("selected");
			$("#registerbox").slideDown();
		}

		e.preventDefault();
	});
	
	$("#topmenu > #nav > #box").click(function(e) {
		if ($("#registerbox").is(":visible")) {
			$("#registerbox").slideUp(400, function() { $("#topmenu > #nav > #login > #register").removeClass("selected"); });
			$("#topmenu > #nav > #login > #register").removeClass("selected");
		}
		
		if ($("#signinbox").is(":visible")) {
			$("#signinbox").slideUp(400, function() { $("#topmenu > #nav > #login > #signin").removeClass("selected"); });
		}
		
		if (!$("#topmenu > #nav > #box > ul").is(":visible")) {
			$(this).addClass("selected");
			$("#topmenu > #nav > #box > ul").slideDown();
			
			e.preventDefault();
		}
	});
	
	var day = $("#registerbox ul li.birthdate input#day").val();
	$("#registerbox ul li.birthdate .combo-day ul li").each(function() {
		if ($(this).html() == day) {
			$(this).addClass("selected");
			return;
		}
	});
	
	var month = $("#registerbox ul li.birthdate input#month").val();
	$("#registerbox ul li.birthdate .combo-month ul li").each(function() {
		if ($(this).html() == month) {
			$(this).addClass("selected");
			return;
		}
	});
	
	var year = $("#registerbox ul li.birthdate input#year").val();
	$("#registerbox ul li.birthdate .combo-year ul li").each(function() {
		if ($(this).html() == year) {
			$(this).addClass("selected");
			return;
		}
	});
	
	$("#registerbox ul li.birthdate input").click(function() {
		if ($(this).attr("id") == "day")
			$("#registerbox ul li.birthdate .combo-day").slideDown();
			
		if ($(this).attr("id") == "month")
			$("#registerbox ul li.birthdate .combo-month").slideDown();
			
		if ($(this).attr("id") == "year")
			$("#registerbox ul li.birthdate .combo-year").slideDown();
	});
	
	$("#registerbox ul li.birthdate div ul li").click(function() {
		var text = $(this).html();
		
		if ($(this).parents(".combo-day").length > 0) {
			$("#registerbox ul li.birthdate input#day").val(text);
		
			$("#registerbox ul li.birthdate .combo-day ul li.selected").removeClass("selected");
			$(this).addClass("selected");
			
			$("#registerbox ul li.birthdate .combo-day").slideUp();
		}
		
		if ($(this).parents(".combo-month").length > 0) {
			$("#registerbox ul li.birthdate input#month").val(text);
		
			$("#registerbox ul li.birthdate .combo-month ul li.selected").removeClass("selected");
			$(this).addClass("selected");
			
			$("#registerbox ul li.birthdate .combo-month").slideUp();
		}
		
		if ($(this).parents(".combo-year").length > 0) {
			$("#registerbox ul li.birthdate input#year").val(text);
		
			$("#registerbox ul li.birthdate .combo-year ul li.selected").removeClass("selected");
			$(this).addClass("selected");
			
			$("#registerbox ul li.birthdate .combo-year").slideUp();
		}
	});
	
	$(document).bind('click', function(e) {
		var $clicked = $(e.target);
		
		// Menu
		if ($clicked.parents("#nav").length == 0) {
			$("#topmenu > #nav > #box > ul:visible").slideUp(400, function() { $(this).parent().removeClass("selected"); });
		}
		
		// Signin box
		if ($clicked.parents("#signinbox, #signin").length == 0) {
			$("#signinbox:visible").slideUp(400, function() { $("#signin").removeClass("selected") });
		}
		
		// Birth date selectors
		if ($clicked.attr("id") != "day")
			$("#registerbox ul li.birthdate .combo-day").slideUp();
		
		if ($clicked.attr("id") != "month")
			$("#registerbox ul li.birthdate .combo-month").slideUp();
		
		if ($clicked.attr("id") != "year")
			$("#registerbox ul li.birthdate .combo-year").slideUp();
	});
	
	$("#bottom #urls table tbody tr td:first-child, #bottom #users table tbody tr td:first-child").each(function() {
		$(this).click(function() {
			if ($(this).attr("id") == "off") {
				$(this).attr("id", "on");
			} else {
				$(this).attr("id", "off");
			}
		});
	});
	
	/* Popup */
	if ($(".popup > .inner > .content").length > 0) {
		$(".popup").slideDown();
		
		$(".popup > .inner > .content > a > #link").click(function() {
			$(this).select();
		});
		
		$(".popup > .inner > .content > a#short-url").zclip({ path:'js/ZeroClipboard.swf',copy:function(){return $(".popup > .inner > .content > a > #link").val();}});
		
		$(".popup > .inner > .content > #closeModal").click(function() {
			$(".popup").slideUp();
		});
	}
	
	if ($("#message-wrapper").length > 0) {
		$("#message-wrapper").slideDown();
	
		$("#message-wrapper > #message > ul > li#closeModal > a").click(function() {
			$("#message").slideUp();
		});
	}
	
	// Update statistics
	if ($(".stats").length > 0) {
		function refreshStats() {
			if (!$("body").hasClass("hidden")) {
				$.getJSON('inc/stats.php', function(data) {
					$.each(data, function(index, value) {
						if (index == 'urls') {
							$(".stats > .boxes > .box > .number#urls-count").html(value);
						} else if (index == 'visits') {
							$(".stats > .boxes > .box > .number#visits-count").html(value);
						} else if (index == 'users') {
							$(".stats > .boxes > .box > .number#users-count").html(value);
						}
					});
				});
			}
		}
		
		refreshStats();
		window.setInterval(refreshStats, 10000);
	}
});

/* Select text */
jQuery.fn.selectText=function(){var d=document,b=this[0],a,c;if(d.body.createTextRange){a=document.body.createTextRange();a.moveToElementText(b);a.select()}else{if(window.getSelection){c=window.getSelection();a=document.createRange();a.selectNodeContents(b);c.removeAllRanges();c.addRange(a)}}};

/* Detect if window/tab is active */
(function(){var hidden="hidden";if(hidden in document){document.addEventListener("visibilitychange",onchange)}else{if((hidden="mozHidden") in document){document.addEventListener("mozvisibilitychange",onchange)}else{if((hidden="webkitHidden") in document){document.addEventListener("webkitvisibilitychange",onchange)}else{if((hidden="msHidden") in document){document.addEventListener("msvisibilitychange",onchange)}else{if("onfocusin" in document){document.onfocusin=document.onfocusout=onchange}else{window.onpageshow=window.onpagehide=window.onfocus=window.onblur=onchange}}}}}function onchange(evt){var v="visible",h="hidden",evtMap={focus:v,focusin:v,pageshow:v,blur:h,focusout:h,pagehide:h};evt=evt||window.event;if(evt.type in evtMap){document.body.className=evtMap[evt.type]}else{document.body.className=this[hidden]?"hidden":"visible"}}})();

/* Truncate text */
!function($){"use strict";function findTruncPoint(dim,max,txt,start,end,$worker,token,reverse){var makeContent=function(content){$worker.text(content);$worker[reverse?"prepend":"append"](token)};var opt1,opt2,mid,opt1dim,opt2dim;if(reverse){opt1=start===0?"":txt.slice(-start);opt2=txt.slice(-end)}else{opt1=txt.slice(0,start);opt2=txt.slice(0,end)}if(max<$worker.html(token)[dim]()){return 0}makeContent(opt2);opt1dim=$worker[dim]();makeContent(opt1);opt2dim=$worker[dim]();if(opt1dim<opt2dim){return end}mid=parseInt((start+end)/2,10);opt1=reverse?txt.slice(-mid):txt.slice(0,mid);makeContent(opt1);if($worker[dim]()===max){return mid}if($worker[dim]()>max){end=mid-1}else{start=mid+1}return findTruncPoint(dim,max,txt,start,end,$worker,token,reverse)}$.fn.truncate=function(options){if(options&&!!options.center&&!options.side){options.side="center";delete options.center}if(options&&!/^(left|right|center)$/.test(options.side)){delete options.side}var defaults={width:"auto",token:"&hellip;",side:"right",addclass:false,addtitle:false,multiline:false,assumeSameStyle:false};options=$.extend(defaults,options);var fontCSS;var $element;var $truncateWorker;var elementText;if(options.assumeSameStyle){$element=$(this[0]);fontCSS={fontFamily:$element.css("fontFamily"),fontSize:$element.css("fontSize"),fontStyle:$element.css("fontStyle"),fontWeight:$element.css("fontWeight"),"font-variant":$element.css("font-variant"),"text-indent":$element.css("text-indent"),"text-transform":$element.css("text-transform"),"letter-spacing":$element.css("letter-spacing"),"word-spacing":$element.css("word-spacing"),display:"none"};$truncateWorker=$("<span/>").css(fontCSS).appendTo("body")}return this.each(function(){$element=$(this);elementText=$element.text();if(!options.assumeSameStyle){fontCSS={fontFamily:$element.css("fontFamily"),fontSize:$element.css("fontSize"),fontStyle:$element.css("fontStyle"),fontWeight:$element.css("fontWeight"),"font-variant":$element.css("font-variant"),"text-indent":$element.css("text-indent"),"text-transform":$element.css("text-transform"),"letter-spacing":$element.css("letter-spacing"),"word-spacing":$element.css("word-spacing"),display:"none"};$truncateWorker=$("<span/>").css(fontCSS).text(elementText).appendTo("body")}else{$truncateWorker.text(elementText)}var originalWidth=$truncateWorker.width();var truncateWidth=parseInt(options.width,10)||$element.width();var dimension="width";var truncatedText,originalDim,truncateDim;if(options.multiline){$truncateWorker.width($element.width());dimension="height";originalDim=$truncateWorker.height();truncateDim=$element.height()+1}else{originalDim=originalWidth;truncateDim=truncateWidth}truncatedText={before:"",after:""};if(originalDim>truncateDim){var truncPoint,truncPoint2;$truncateWorker.text("");if(options.side==="left"){truncPoint=findTruncPoint(dimension,truncateDim,elementText,0,elementText.length,$truncateWorker,options.token,true);truncatedText.after=elementText.slice(-1*truncPoint)}else if(options.side==="center"){truncateDim=parseInt(truncateDim/2,10)-1;truncPoint=findTruncPoint(dimension,truncateDim,elementText,0,elementText.length,$truncateWorker,options.token,false);truncPoint2=findTruncPoint(dimension,truncateDim,elementText,0,elementText.length,$truncateWorker,"",true);truncatedText.before=elementText.slice(0,truncPoint);truncatedText.after=elementText.slice(-1*truncPoint2)}else if(options.side==="right"){truncPoint=findTruncPoint(dimension,truncateDim,elementText,0,elementText.length,$truncateWorker,options.token,false);truncatedText.before=elementText.slice(0,truncPoint)}if(options.addclass){$element.addClass(options.addclass)}if(options.addtitle){$element.attr("title",elementText)}truncatedText.before=$truncateWorker.text(truncatedText.before).html();truncatedText.after=$truncateWorker.text(truncatedText.after).html();$element.empty().html(truncatedText.before+options.token+truncatedText.after)}if(!options.assumeSameStyle){$truncateWorker.remove()}});if(options.assumeSameStyle){$truncateWorker.remove()}}}(jQuery);