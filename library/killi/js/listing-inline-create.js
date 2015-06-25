var listing_create = {
	original_remove_btn: null,
	init: function()
	{
		$('.listing-btn-add-item').each(function()
		{
			$(this).prev().data('original-tr', $(this).prev().find('.listing-tr-inline-create'));
			$(this).prev().find('.listing-tr-inline-create').remove();

			$(this).click(function()
			{
				item = $(this).prev().data('original-tr').clone();
				item.show();
				listing_create.set_remove_listing_new_item(item.find('.listing-btn-remove-item'));
				$(this).prev().find('.table_list>tbody').append(item);
			});

		});
	},
	set_remove_listing_new_item: function(btn)
	{
		btn.click(function(){
			$(this).parent().remove();
		});
	}
}
$(document).ready(function()
{
	listing_create.init();
});