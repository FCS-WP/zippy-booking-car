<?php
global $product;
$args = array(
  'post_type'      => 'product',
  'posts_per_page' => '',
  'orderby'        => 'menu_order',
  'order'          => 'ASC',
  'post_status'      => 'publish',
);

$query = new WP_Query($args);

if ($query->have_posts()) {
?>
  <div class="products-grid">

    <?php while ($query->have_posts()) {
      $query->the_post();
      global $product;
      $full_description = $product->get_description();
    ?>
      <div class="products-row">
        <div class="product-item-col">
          <?php echo get_the_post_thumbnail(get_the_ID(), 'full'); ?>

        </div>
        <div class="product-item-col">
          <h2 class="product-title"> <?php echo get_the_title(); ?></h2>
          <?php if (!is_user_logged_in()) : ?>
            <div class="product-full-description"><?php echo $full_description; ?></div>
          <?php endif; ?>
        </div>
        <div class="product-item-col center-product-col">
          <button><a href="<?php echo get_the_permalink(); ?>">Enquire Now</a></button>
        </div>
      </div>
    <?php } ?>

  </div>
<?php } else { ?>
  <p>No products found.</p>
<?php
}

wp_reset_postdata();
