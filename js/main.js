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
	
	$("#bottom #urls table tbody tr td:first-child").click(function() {
		if ($(this).attr("id") == "off") {
			$(this).attr("id", "on");
		} else {
			$(this).attr("id", "off");
		}
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
		
		refreshStats();
		window.setInterval(refreshStats, 10000);
	}
});

/* Select text */
jQuery.fn.selectText=function(){var d=document,b=this[0],a,c;if(d.body.createTextRange){a=document.body.createTextRange();a.moveToElementText(b);a.select()}else{if(window.getSelection){c=window.getSelection();a=document.createRange();a.selectNodeContents(b);c.removeAllRanges();c.addRange(a)}}};