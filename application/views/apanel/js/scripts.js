$(document).ready(function(){$("tr:nth-child(even)").addClass("even");$("a[rel*=facebox]").facebox();$("#myTable").tablesorter();$(".canhide").append("<div class='close-notification'></div>").css("position","relative");$(".close-notification").live("click",function(){$(this).hide();$(this).parent().fadeOut(600)});$("#tabs div.tab_box").hide();$("#tabs div.tab_box:first").show();$("#tabs ul.tab_link li:first").addClass("active");$("#tabs ul.tab_link li a").click(function(){$("#tabs ul.tab_link li").removeClass("active");$(this).parent().addClass("active");var a=$(this).attr("href");$("#tabs div").hide();$(a).show();return false})});