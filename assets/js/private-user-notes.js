 function print_private_user_notes()
{
	 var contents = jQuery("#private-user-notes").html();
			var frame1 = jQuery('<iframe />');
			frame1[0].name = "frame1";
			frame1.css({ "position": "absolute", "top": "-1000000px" });
			jQuery("body").append(frame1);
			var frameDoc = frame1[0].contentWindow ? frame1[0].contentWindow : frame1[0].contentDocument.document ? frame1[0].contentDocument.document : frame1[0].contentDocument;
			frameDoc.document.open();
			frameDoc.document.write('<style>@page { size: auto;  margin: 30px;} </style>');
			frameDoc.document.write(contents);
			frameDoc.document.close();
			setTimeout(function () {
				window.frames["frame1"].focus();
				window.frames["frame1"].print();
				frame1.remove();
			}, 500);
}
