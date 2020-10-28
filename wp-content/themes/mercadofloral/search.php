<?php get_template_part('templates/page', 'header'); ?>

<div class="container pb-5">
	<?php if (!have_posts()) : ?>
		<p class="font-size--5">No se encontraron resultados para tu búsqueda. </p>
		<?php get_search_form(); ?>
	<?php endif; ?>
	<div class="row">
		<?php while (have_posts()) : global $post; the_post(); ?>
			<div class="col-md-4 mb-4">
				<?php  print_card($post); //get_template_part('templates/content', 'search'); ?>
			</div>
		<?php endwhile; ?>
	</div>
</div>

<?php //the_posts_navigation(); ?>
