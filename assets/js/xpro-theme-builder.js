(function($){
    $( document ).ready(function() {
        setTimeout(function () {
            $('.xpro-theme-builder-frontend').show();
        },2000);
        if($('.xtb-header-sticky').length){
            var divHeight = $('.xpro-theme-builder-header-nav').height();;
            $(window).on('scroll', function () {
                if ($(this).scrollTop() > 220) {
                    $('.xtb-header-sticky').css('min-height', divHeight+'px');
                    $('.xtb-header-sticky').addClass('xtb-appear');
                }
                else {
                    $('.xtb-header-sticky').removeClass('xtb-appear');
                }
            });
        }
    });
})(jQuery);