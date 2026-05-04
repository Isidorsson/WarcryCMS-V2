// JavaScript Document

$(document).ready(function()
{
    //Bind Post Delete Buttons
	$('.post-delete-button').on('click', function()
	{
		$(this).WarcryAlertBox('open', '<p>Are you sure you want to delete this post?</p>',
		{
			0: { 
				text: 'Yes', onclick: function(event)
				{
					var Caller = $.fn.WarcryAlertBox('getCaller');
					var PostId = parseInt(Caller.attr('data-post-id'), 10);

					// Close the confirmation popup immediately to prevent it from staying stuck.
					$.fn.WarcryAlertBox('close');

					$.get($BaseURL + '/ajax.php?phase=17', 
					{ 
						id: PostId
					},
					function(data)
					{
						if ($.trim(data) == 'OK')
						{
							// Refresh after a tiny delay so the modal has time to close cleanly.
							setTimeout(function()
							{
								window.location.reload();
							}, 150);
						}
						else
						{
							$.fn.WarcryAlertBox('open', '<p>Error: '+data+'</p>');
						}
					});	
					
					return false;
				}
			},
			1: { text: 'No', onclick: 'close' }
		});
		
		return false;
	});
	
	//Bind Post Quote Buttons
	$('.post-quote-button').on('click', function()
	{
		var PostId = $(this).attr('data-post-id');
		
		//Pull info about the post
		$.get($BaseURL + '/ajax.php?phase=18', 
		{ 
			id: PostId,
		},
		function(data)
		{
			//Check for error
			if (typeof data.error == 'undefined')
			{
				var PostText = data.text;
				var PostAuthor = data.author;
				var QuoteText = '[quote='+PostAuthor+']'+PostText+'[/quote]' + "\n";
				
				//Focus the text area
				$('#quick_reply_textarea').focus();
				//Append the text
				$('#quick_reply_textarea').html(QuoteText);
				//Update the advanced button href
				$('#go-advanced-post').attr('href', $('#go-advanced-post').attr('href') + '&quote=' + PostId);
			}
			else
			{
				$.fn.WarcryAlertBox('open', '<p>Error: '+data.error+'</p>');
			}
		});	

		return false;
	});
});