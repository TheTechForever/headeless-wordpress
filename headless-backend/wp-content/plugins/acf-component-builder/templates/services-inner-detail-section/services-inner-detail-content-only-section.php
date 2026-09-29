<?php
/**
 * Services Inner Detail — Content Only.
 * @var array $acb
 */
if (!defined('ABSPATH')) { exit; }

$title = get_sub_field('services_inner_content_only_title');
$head  = new \ACB\Render\Heading_Renderer();
$wys   = new \ACB\Render\Wysiwyg_Renderer();
?>
<section class="services-inner-detail services-inner-detail--content-only">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-10 services-content-col">
        <?php echo $head->render($title, ['tag' => 'h2', 'class' => 'services-title text-center']); ?>
        <?php if (have_rows('services_inner_content_only_wrap')) : ?>
          <div class="services-content-wrap">
            <?php while (have_rows('services_inner_content_only_wrap')) : the_row(); ?>
              <div class="services-content-item">
                <?php echo $head->render(get_sub_field('content_title'), ['tag' => 'h3', 'class' => 'services-item-title']); ?>
                <?php echo $wys->render(get_sub_field('content_text'), ['class' => 'services-item-text']); ?>
              </div>
            <?php endwhile; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
