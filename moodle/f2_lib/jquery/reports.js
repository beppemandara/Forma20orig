/* 
 * $Id: reports.js 1008 2012-07-31 15:36:01Z l.moretto $
 */
/** 
 * POST Request: Simple rich user experience - jquery.fileDownload.js & jQuery UI Dialog
 * uses data "options" argument to create a POST request from a form to initiate a file download
 * the below uses jQuery "on" http://api.jquery.com/on/ (jQuery 1.7 + required, otherwise use "delegate" or "live") so that any
 * <form class="fileDownloadForm" ... that is ever loaded into an Ajax site will automatically use jquery.fileDownload.js instead of the defualt form submit behavior
 * if you are using "on":
 *           you should generally be able to reduce the scope of the selector below "document" but it is used in this example so it
 *           works for possible dynamic manipulation in the entire DOM.
 *
 * $(document).on("submit", "form.mform", function (e) {
 * //alert($(this).serialize());
 * 	$.fileDownload($(this).attr('action'), 
 * 	{
 * 		preparingMessageHtml: "We are preparing your report, please wait...",
 * 		failMessageHtml: "There was a problem generating your report, please try again.",
 * 		httpMethod: "POST",
 * 		data: $(this).serialize()
 * 	});
 * 	e.preventDefault(); //otherwise a normal form submit would occur
 * });
*/
$(document).on("click", "a.fileDownload", function () {
    $.fileDownload($(this).attr('href'), {
        preparingMessageHtml: "We are preparing your report, please wait...",
        failMessageHtml: "There was a problem generating your report, please try again."
    });
    return false; //this is critical to stop the click event which will trigger a normal file download!
});
