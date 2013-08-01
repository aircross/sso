$(document).ready(function() {

   // We assume we'll be called with href blah?n.  If so,
   // automagically view that section.

   var str = $(location).attr('href');
   var request = str.substr(str.lastIndexOf("?")+1);
   var header = "[id=section_header_"+request+"]";
   var section = "[id=section_"+request+"]";
   var showhide = "[id=showhide_"+request+"]";

   $(header).addClass("viewing");
   $(section).addClass("viewing");
   $(showhide).html("Hide");

   // This could be dynamic and cool.  For now, though, it's hardcoded and lame.
   // Why aren't we using jquery for this?

   $('.section_header').click(function() {

      // If we are currently viewing, then we need to hide our children
      // [hide our wives, hide our husbands, because they're clicking out here.]
      // otherwise, we show our children.

      if ($(this).attr("id") == "section_header_1") {
         if ($(this).hasClass("viewing")) {
            $('#section_1').removeClass("viewing");
            $('#showhide_1').html("Show");
            $(this).removeClass("viewing");
         }
         else {
            $('#section_1').addClass("viewing");
            $('#showhide_1').html("Hide");
            $(this).addClass("viewing");
         }
      }
      else if ($(this).attr("id") == "section_header_2") {
         if ($(this).hasClass("viewing")) {
            $('#section_2').removeClass("viewing");
            $('#showhide_2').html("Show");
            $(this).removeClass("viewing");
         }
         else {
            $('#section_2').addClass("viewing");
            $('#showhide_2').html("Hide");
            $(this).addClass("viewing");
         }
      }
      else if ($(this).attr("id") == "section_header_3") {
         if ($(this).hasClass("viewing")) {
            $('#section_3').removeClass("viewing");
            $('#showhide_3').html("Show");
            $(this).removeClass("viewing");
         }
         else {
            $('#section_3').addClass("viewing");
            $('#showhide_3').html("Hide");
            $(this).addClass("viewing");
         }
      }
      else if ($(this).attr("id") == "section_header_4") {
         if ($(this).hasClass("viewing")) {
            $('#section_4').removeClass("viewing");
            $('#showhide_4').html("Show");
            $(this).removeClass("viewing");
         }
         else {
            $('#section_4').addClass("viewing");
            $('#showhide_4').html("Hide");
            $(this).addClass("viewing");
         }
      }
      else if ($(this).attr("id") == "section_header_5") {
         if ($(this).hasClass("viewing")) {
            $('#section_5').removeClass("viewing");
            $('#showhide_5').html("Show");
            $(this).removeClass("viewing");
         }
         else {
            $('#section_5').addClass("viewing");
            $('#showhide_5').html("Hide");
            $(this).addClass("viewing");
         }
      }
      else if ($(this).attr("id") == "section_header_6") {
         if ($(this).hasClass("viewing")) {
            $('#section_6').removeClass("viewing");
            $('#showhide_6').html("Show");
            $(this).removeClass("viewing");
         }
         else {
            $('#section_6').addClass("viewing");
            $('#showhide_6').html("Hide");
            $(this).addClass("viewing");
         }
      }
      else {
         // alert("invalid section #");
      }
   });
});
