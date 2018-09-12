jQuery(document).ready(function($){

    $('#modalmorereviewsbtn').on('click', function(e){
        e.preventDefault();
        var productId = $('#modalmorereviewsbtn').attr('sid');
        var cats = $('#modalmorereviewsbtn').attr('cats');
        var data = {
            'action' : 'ajaxGetAllReviews',
            'pref_cs' : productId,
            'lang' : suppliers_ajax_vars.lang,
            'cats' : cats,
        };
        $('#loadallreviews').html('');
        $('#loadallreviews').html('<div class="ajaxIconWrapper"><div class="ajaxIcon"><img src="' + suppliers_ajax_vars.template_uri + '/images/common/icons/ajaxloader.png" alt="Loading..."></div></div>');

        $.get(suppliers_ajax_vars.site_url + '/api/?load=Suppliers', data, function (response) {
            $('#loadallreviews').html(response);
            ShowHideMore = $('#loadallreviews > ul');
            ShowHideMore.each(function() {

                var $times = $(this).children('li');
                if ($times.length > 5) {
                    ShowHideMore.children(':nth-of-type(n+4)').addClass('moreShown').hide();
                    $(this).find('span.message').addClass('more-times').html('Show more');
                }
            });
        });
    });

});