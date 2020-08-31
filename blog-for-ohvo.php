<?php
/*
Plugin Name: Blog Masonry Grid.
Description: Blog Masonry Grid for OHVO Website.
Version: 1.0
Author: OHVOPlugins
Text Domain: blog-for-ohvo
*/

if ( ! defined('ABSPATH')) exit;  // if direct access 

/**
* Custom Field For Category - color
**/

// Add new term meta to Add term page
function my_taxonomy_add_meta_fields( $taxonomy ) { ?>
    <div class="form-field term-group">
        <label for="color">
          <?php _e( 'Color of Category', 'codilight-lite' ); ?> <input type="text" id="color" name="color" />
        </label>
    </div><?php
}
add_action( 'category_add_form_fields', 'my_taxonomy_add_meta_fields', 10, 2 );

// Add new term meta to Edit term page
function my_taxonomy_edit_meta_fields( $term, $taxonomy ) {
    $color = get_term_meta( $term->term_id, 'color', true ); ?>

    <tr class="form-field term-group-wrap">
        <th scope="row">
            <label for="color"><?php _e( 'Color of Category', 'codilight-lite' ); ?></label>
        </th>
        <td>
            <input type="text" id="color" name="color" value="<?php echo $color; ?>" />
        </td>
    </tr><?php
}
add_action( 'category_edit_form_fields', 'my_taxonomy_edit_meta_fields', 10, 2 );

// Save custom meta
function my_taxonomy_save_taxonomy_meta( $term_id, $tag_id ) {
    if ( isset( $_POST[ 'color' ] ) ) {
        update_term_meta( $term_id, 'color', $_POST[ 'color' ] );
    } else {
        update_term_meta( $term_id, 'color', '' );
    }
}
add_action( 'created_category', 'my_taxonomy_save_taxonomy_meta', 10, 2 );
add_action( 'edited_category', 'my_taxonomy_save_taxonomy_meta', 10, 2 );


add_action( 'wp_enqueue_scripts', 'ohvo_blog_grid_enqueue_scripts' );

/**
 * Audio Gallery Player enqueue scripts
 */
if (!function_exists('ohvo_blog_grid_enqueue_scripts')) {
	function ohvo_blog_grid_enqueue_scripts( $hook ) {
		
    wp_enqueue_style( 
      'ohvo-blog-grid-css', 
      plugins_url( '/ohvo-blog-grid.css', __FILE__ ), 
      array(), 
      '', 
      'all' 
    );


    wp_enqueue_script( 
      'masonry', 
      plugins_url( '/js/masonry.pkgd.min.js', __FILE__ ) 
    );
    
    wp_register_script(
      'ohvo-blog-grid-js', 
      plugins_url( '/ohvo-blog-grid.js', __FILE__ ), 
      array('masonry', 'jquery'), 
      '', 
      true
    );

    wp_localize_script( 
      'ohvo-blog-grid-js', 
      'ajax_posts', 
      array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'noposts' => __('No older posts found', 'ohvo'),
      )
    );
    wp_enqueue_script( 'ohvo-blog-grid-js' );
	}
}

/**
 * Main Layout that show posts
 * @shortcode [masonry_grid_posts]
 */
function ohvo_masonry_grid_posts($attr, $content) {

    ob_start(); 
    ?>
    <div class="ohvo-posts-grid">
        <div class="filter">
            <p>Follow our publications and stay up to date with the latest news.</p>
            <a href="javascript:void(0)" class="view-all">View all</a>
        </div>        
        <div class="loading">
          <div class="spinner">
            <div class="rect1"></div>
            <div class="rect2"></div>
            <div class="rect3"></div>
            <div class="rect4"></div>
            <div class="rect5"></div>
          </div>
        </div>
        <div class="ohvo-container container">
            <div id="ohvo-ajax-posts" class="grid">
            </div>
        </div>
        <div id="ohvo-pagination-wrap"></div>
    </div>
    <?php return $content . ob_get_clean();
}
add_shortcode( 'masonry_grid_posts', 'ohvo_masonry_grid_posts' );


/**
 * Get Posts and Create Post Grid using wp ajax
 */
function more_post_ajax() {
    header("Content-Type: text/html");

    $args = $_REQUEST;

    if ($args['category_name'] == -1)
      $loop = new WP_Query( 
                        array(
                            'post_type'         => $_REQUEST['post_type'],
                            'column_size'       => $_REQUEST['column_size'],
                            'posts_per_page'    => $_REQUEST['posts_per_page'],                
                            'paged'             => $_REQUEST['paged']
                        )
                  );
    else
      $loop = new WP_Query( $args );
    $out = '';
    $index = 0;
    if ($loop -> have_posts()) : while ($loop-> have_posts()) : $loop-> the_post();
        $class = 'post grid-item';
        if ($index == 0) {
            $class .= ' grid-item--height2';
        }
        elseif ($index < 5) {
            $class .= ' no-featured-image';
        }
        $featured_img = get_the_post_thumbnail();
        if (empty($featured_img)) {
            //$featured_img = '<img src="https://dev.ohvo.com/wp-content/uploads/2020/05/Macbook.png">';
            $featured_img = '<img src="https://www.lfmaudio.com/wp-content/uploads/2020/05/How-to-make-a-radio-show-interesting.jpg">';
        }

        $category = get_the_category();

        $category_list = '<span class="category-list">';
        foreach ($category as $key => $cat) {
          $cat_link = get_term_link( $cat->term_id );
          $color = get_term_meta( $cat->term_id, 'color', true );
          $category_list .= '<a href="javascript:void(0)" class="category-name" style="background:' . $color . ';">'.$cat->name.'</a>';
        }
        $category_list .= '</span>';


        $out .= ' <article class="' . $class . '">
                    <div class="post-item">
                      ' . $category_list . '
                      <div class="featured-image"><a href="'.get_the_permalink().'">'.$featured_img.'</a></div>
                      <p class="published-at">'.get_the_date().'</p>
                      <h2 class="title"><a href="'.get_the_permalink().'">'.get_the_title().'</a></h2>
                    </post-item>
                  </article>';
            $index++;
        endwhile;
    endif;
    $out .= misha_paginator($args['paged'], $args['posts_per_page'], $args['category_name']);
    wp_reset_postdata();
    $out .= "<span id='page-size'>$loop->max_num_pages</span>";
    die($out);
}

add_action( 'wp_ajax_nopriv_more_post_ajax', 'more_post_ajax' );
add_action( 'wp_ajax_more_post_ajax', 'more_post_ajax' );

/**
 * Create Pagination
 *
 * @param   number  $current_page     number of current page.
 * @param   number  $posts_per_page   number of posts in one page.
 * @param   string  $category_name    name of category that should be showen.

 * @return  string  HTML content.
 */
function misha_paginator( $current_page = 1, $posts_per_page = 14, $category_name = -1 ){

    if ($category_name == -1)      
      $posts = new WP_Query(
          array(
              'post_type'         => 'post',
              'posts_per_page'    => $posts_per_page
          )
      );
    else 
      $posts = new WP_Query(
          array(
              'post_type'         => 'post',
              'category_name'     => $category_name,
              'posts_per_page'    => $posts_per_page
          )
      );
  
  // the overall amount of pages
  $max_page = $posts->max_num_pages;

  // we don't have to display pagination or load more button in this case
  if( $max_page <= 1 ) 
  {
    $pagination = '<nav id="misha_pagination" class="navigation pagination" role="navigation"><div class="nav-links"></div></nav>';
    echo str_replace(array("/page/1?", "/page/1\""), array("?", "\""), $pagination);
    return;
  }
 
  // set the current page to 1 if not exists
  if( empty( $current_page ) || $current_page == 0) $current_page = 1;
 
  // you can play with this parameter - how much links to display in pagination
  $links_in_the_middle = 4;
  $links_in_the_middle_minus_1 = $links_in_the_middle-1;
 
  // the code below is required to display the pagination properly for large amount of pages
  // I mean 1 ... 10, 12, 13 .. 100
  // $first_link_in_the_middle is 10
  // $last_link_in_the_middle is 13
  $first_link_in_the_middle = $current_page - floor( $links_in_the_middle_minus_1/2 );
  $last_link_in_the_middle = $current_page + ceil( $links_in_the_middle_minus_1/2 );
 
  // some calculations with $first_link_in_the_middle and $last_link_in_the_middle
  if( $first_link_in_the_middle <= 0 ) $first_link_in_the_middle = 1;
  if( ( $last_link_in_the_middle - $first_link_in_the_middle ) != $links_in_the_middle_minus_1 ) { $last_link_in_the_middle = $first_link_in_the_middle + $links_in_the_middle_minus_1; }
  if( $last_link_in_the_middle > $max_page ) { $first_link_in_the_middle = $max_page - $links_in_the_middle_minus_1; $last_link_in_the_middle = (int) $max_page; }
  if( $first_link_in_the_middle <= 0 ) $first_link_in_the_middle = 1;
 
  // begin to generate HTML of the pagination
  $pagination = '<nav id="misha_pagination" class="navigation pagination" role="navigation"><div class="nav-links">';
 
  // when to display "..." and the first page before it
  if ($first_link_in_the_middle >= 3 && $links_in_the_middle < $max_page) {
    $pagination.= '<a href="javascript:void(0)" class="page-button" page-number="1">1</a>';
 
    if( $first_link_in_the_middle != 2 )
      $pagination .= '<span class="page-button extend">...</span>';
 
  }
 
  // arrow left (previous page)
  if ($current_page != 1)
    $pagination.= '<a href="javascript:void(0)" class="prev page-button" page-number="'.($current_page-1).'">Prev</a>';
 
 
  // loop page links in the middle between "..." and "..."
  for($i = $first_link_in_the_middle; $i <= $last_link_in_the_middle; $i++) {
    if($i == $current_page) {
      $pagination.= '<span class="page-button current">'.$i.'</span>';
    } else {
      $pagination .= '<a href="javascript:void(0)" class="page-button" page-number="'.$i.'">'.$i.'</a>';
    }
  }
 
  // arrow right (next page)
  if ($current_page != $last_link_in_the_middle )
    $pagination.= '<a href="javascript:void(0)" class="next page-button" page-number="'.($current_page+1).'">Next</a>';
 
 
  // when to display "..." and the last page after it
  if ( $last_link_in_the_middle < $max_page ) {
 
    if( $last_link_in_the_middle != ($max_page-1) )
      $pagination .= '<span class="page-button extend">...</span>';
 
    $pagination .= '<a href="javascript:void(0)" class="page-button" page-number="'.$max_page.'">'. $max_page .'</a>';
  }
 
  // end HTML
  $pagination.= "</div></nav>\n";
 
  // replace first page before printing it
  echo str_replace(array("/page/1?", "/page/1\""), array("?", "\""), $pagination);
}

?>