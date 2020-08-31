jQuery(document).ready(function (){ // use jQuery code inside this to avoid "$ is not defined" error
    let ppp = 8; // post per page
    let pageNumber = 0;
    let filter = -1;
    let category_name = -1;


    $viewAllButtons = jQuery('.ohvo-posts-grid .view-all');

    if ($viewAllButtons.length)
        loadPosts()

    // masonry grid
    $grid = jQuery('#ohvo-ajax-posts.grid').masonry({
        // options
        itemSelector: '.grid-item',
        columnWidth: '.grid-item',
        percentPosition: true
    });

    jQuery(document).on('click', '.ohvo-posts-grid nav a.page-button', function(){
        $page_number = jQuery(this).attr('page-number');
        changeAjaxPagination($page_number);
    })

    function changeAjaxPagination(page_number = 1) {
        if (pageNumber == page_number) {
            return;
        }
        pageNumber = page_number;
        $grid.masonry('remove', jQuery('.ohvo-posts-grid article.post')).masonry('layout');
        loadPosts();
    }

    function loadPosts() {

        let q = '&post_type=post' + '&paged=' + pageNumber + '&posts_per_page=' + ppp + '&column_size=3' + '&category_name=' + category_name + '&action=more_post_ajax';

        jQuery('.ohvo-posts-grid .loading').css('display', 'block');
        
        jQuery.ajax({
            type: "POST",
            dataType: 'html',
            url: ajax_posts.ajaxurl,
            data: q,
            success: function(data) {
                let $data = jQuery(data);

                jQuery('#ohvo-pagination-wrap').html($data.slice(0, 1));
                if($data.length) {
                    const $items = $data.slice(1, $data.length - 1);
                    $grid.append($items).masonry('appended', $items).masonry('layout');
                }
                jQuery('.ohvo-posts-grid .loading').css('display', 'none');
            },
            error: function() {
                console.error('error while loading posts');
            }
        })
    }

    jQuery(document).on('click', '.ohvo-posts-grid .view-all', function(){
        pageNumber = 0;
        ppp = -1;
        category_name = -1;
        $grid.masonry('remove', jQuery('.ohvo-posts-grid article.post')).masonry('layout');
        loadPosts();
    })

    jQuery(document).on('click', '.ohvo-posts-grid .category-name', function(){
        pageNumber = 0;
        ppp = 8;
        category_name = jQuery(this).text();
        $grid.masonry('remove', jQuery('.ohvo-posts-grid article.post')).masonry('layout');
        loadPosts();
    })

});