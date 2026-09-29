<?php
/**
 * Services Inner Detail — Left Image / Right Content with background colour.
 * @var array $acb
 */
if (!defined('ABSPATH')) { exit; }

$image = get_sub_field('services_inner_left_image');
$title = get_sub_field('services_inner_right_title');
$img   = new \ACB\Render\Image_Renderer();
$head  = new \ACB\Render\Heading_Renderer();
$wys   = new \ACB\Render\Wysiwyg_Renderer();
?>
<section class="services-inner-detail services-inner-detail--img-left has-bg">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-12 col-md-6 services-image-col">
        <?php echo $img->render($image, ['class' => 'services-image', 'size' => 'large']); ?>
      </div>
      <div class="col-12 col-md-6 services-content-col">
        <?php echo $head->render($title, ['tag' => 'h2', 'class' => 'services-title']); ?>
        <?php if (have_rows('services_inner_right_content_wrap')) : ?>
          <div class="services-content-wrap">
            <?php while (have_rows('services_inner_right_content_wrap')) : the_row(); ?>
              <div class="services-content-item">
                <?php echo $head->render(get_sub_field('right_content_title'), ['tag' => 'h3', 'class' => 'services-item-title']); ?>
                <?php echo $wys->render(get_sub_field('right_content_text'), ['class' => 'services-item-text']); ?>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
