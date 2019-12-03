Шаг 1. Добавление кнопки «Загрузить ещё»
	Найдите соответствующее место в шаблоне за пределами цикла while, то есть после того, как заканчивается вывод постов (в TwentySeventeen это место практически сразу после endwhile) и вставляем туда код:
	<?php if (  $wp_query->max_num_pages > 1 ) : ?>
		<script>
		var ajaxurl = '<?php echo site_url() ?>/wp-admin/admin-ajax.php';
		var true_posts = '<?php echo serialize($wp_query->query_vars); ?>';
		var current_page = <?php echo (get_query_var('paged')) ? get_query_var('paged') : 1; ?>;
		var max_pages = '<?php echo $wp_query->max_num_pages; ?>';
		</script>
		<div id="true_loadmore">Загрузить ещё</div>
	<?php endif; ?>
	А теперь немного стилей, которые мы добавим на нашу кнопку, чтобы она круто выглядела (стили можно вставить в стандартный style.css в папке с темой).
	<style>
		#true_loadmore{
			background-color: #ddd; /* сервый фон */
		    	border-radius: 2px; /* закругление углов */
		    	display: block; /* блочный элемент, на случай, если захотите использовать <a> */
		    	text-align: center; /* выравнивание текста по центру */
		    	font-size: 14px; font-size: 0.875rem; /* размер шрифта */
		    	font-weight: 800; /* начертание */
		    	letter-spacing: 1px; /* межбуквенный интервал */
		    	cursor: pointer; /* курсор мыши при наведении такой же, как при наведении на ссылку */
		    	text-transform: uppercase;
		    	padding: 10px 0; /* внутренние отступы сверху и снизу у кнопки */
		    	transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out, color 0.3s ease-in-out; /* CSS-анимация*/
		}
		#true_loadmore:hover{
			background-color: #767676;
			color: #fff;
		}
	</style>


Шаг 2. Подключение скриптов jQuery
	<?php function true_loadmore_scripts() {
		wp_enqueue_script('jquery'); // скорее всего он уже будет подключен, это на всякий случай
	 	wp_enqueue_script( 'true_loadmore', get_stylesheet_directory_uri() . '/loadmore.js', array('jquery') );
	}
	 
	add_action( 'wp_enqueue_scripts', 'true_loadmore_scripts' );
	?>
Шаг 3. Скрипт асинхронной загрузки 
	<?php jQuery(function($){
		$('#true_loadmore').click(function(){
			$(this).text('Загружаю...'); // изменяем текст кнопки, вы также можете добавить прелоадер
			var data = {
				'action': 'loadmore',
				'query': true_posts,
				'page' : current_page
			};
			$.ajax({
				url:ajaxurl, // обработчик
				data:data, // данные
				type:'POST', // тип запроса
				success:function(data){
					if( data ) { 
						$('#true_loadmore').text('Загрузить ещё').before(data); // вставляем новые посты
						current_page++; // увеличиваем номер страницы на единицу
						if (current_page == max_pages) $("#true_loadmore").remove(); // если последняя страница, удаляем кнопку
					} else {
						$('#true_loadmore').remove(); // если мы дошли до последней страницы постов, скроем кнопку
					}
				}
			});
		});
	});
	?>
Шаг 4. Обработчик PHP, вывод постов
	Этот код также отправляется в файл functions.php
	<?php
	function true_load_posts(){
	 
		$args = unserialize( stripslashes( $_POST['query'] ) );
		$args['paged'] = $_POST['page'] + 1; // следующая страница
		$args['post_status'] = 'publish';
	 
		// обычно лучше использовать WP_Query, но не здесь
		query_posts( $args );
		// если посты есть
		if( have_posts() ) :
	 
			// запускаем цикл
			while( have_posts() ): the_post();
	 
				get_template_part( 'template-parts/post/content', get_post_format() );
	 
			endwhile;
	 
		endif;
		die();
	}
	 
	 
	add_action('wp_ajax_loadmore', 'true_load_posts');
	add_action('wp_ajax_nopriv_loadmore', 'true_load_posts');
	?>






Бесконечная загрузка постов при прокрутке страницы

	Во-первых, кнопка Загрузить ещё нам больше не понадобится, поэтому немного изменим HTML-код:

	<?php if (  $wp_query->max_num_pages > 1 ) : ?>
		<script id="true_loadmore">
		var ajaxurl = '<?php echo site_url() ?>/wp-admin/admin-ajax.php';
		var true_posts = '<?php echo serialize($wp_query->query_vars); ?>';
		var current_page = <?php echo (get_query_var('paged')) ? get_query_var('paged') : 1; ?>;
		</script>
	<?php endif; ?>
	Во-вторых, стили CSS нам тоже больше не нужны, если вы их добавляли, можете напрочь удалить.

	В-третьих, содержимое файла loadmore.js изменится и будет следующим:
	<script>
		jQuery(function($){
			$(window).scroll(function(){
				var bottomOffset = 2000; // отступ от нижней границы сайта, до которого должен доскроллить пользователь, чтобы подгрузились новые посты
				var data = {
					'action': 'loadmore',
					'query': true_posts,
					'page' : current_page
				};
				if( $(document).scrollTop() > ($(document).height() - bottomOffset) && !$('body').hasClass('loading')){
					$.ajax({
						url:ajaxurl,
						data:data,
						type:'POST',
						beforeSend: function( xhr){
							$('body').addClass('loading');
						},
						success:function(data){
							if( data ) { 
								$('#true_loadmore').before(data);
								$('body').removeClass('loading');
								current_page++;
							}
						}
					});
				}
			});
		});
	</script>


	Файл functions.php мы оставляем без изменений.







