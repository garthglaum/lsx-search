<?php
/**
 * LSX Search Frontend Class.
 *
 * @package lsx-search
 */
class LSX_Search_Frontend {

	public $options = false;

	public $tabs = false;

	public $facet_data = false;

	public $search_enabled = false;

	public $search_core_suffix = false;

	public $search_prefix = false;

	public $post_types = false;

	public $taxonomies = false;

	/**
	 * Construct method.
	 */
	public function __construct() {
		if ( function_exists( 'tour_operator' ) ) {
			$this->options = get_option( '_lsx-to_settings', false );
		} else {
			$this->options = get_option( '_lsx_settings', false );

			if ( false === $this->options ) {
				$this->options = get_option( '_lsx_lsx-settings', false );
			}
		}

		add_action( 'wp', array( $this, 'set_vars' ), 11 );
		add_action( 'wp', array( $this, 'set_facetwp_vars' ), 12 );
		add_action( 'wp', array( $this, 'core' ), 13 );

		add_filter( 'lsx_search_post_types', array( $this, 'register_post_types' ) );
		add_filter( 'lsx_search_taxonomies', array( $this, 'register_taxonomies' ) );
		add_filter( 'lsx_search_post_types_plural', array( $this, 'register_post_type_tabs' ) );

		add_filter( 'facetwp_sort_options', array( $this, 'facetwp_sort_options' ), 10, 2 );
		add_filter( 'facetwp_load_css', array( $this, 'facetwp_load_css' ), 10, 1 );
		add_filter( 'facetwp_pager_html', array( $this, 'facetwp_pager_html' ), 10, 2 );
		add_filter( 'facetwp_result_count', array( $this, 'facetwp_result_count' ), 10, 2 );
		add_filter( 'facetwp_facet_html', array( $this, 'facetwp_slide_html' ), 10, 2 );
	}

	/**
	 * Check all settings.
	 */
	public function set_vars() {
		$this->post_types = apply_filters( 'lsx_search_post_types', array() );
		$this->taxonomies = apply_filters( 'lsx_search_taxonomies', array() );
		$this->tabs = apply_filters( 'lsx_search_post_types_plural', array() );

		if ( is_search() ) {
			$this->search_core_suffix = 'core';
			$this->search_prefix = 'search';
		} elseif ( is_post_type_archive( $this->post_types ) || is_tax( $this->taxonomies ) ) {
			$this->search_core_suffix = 'search';
			$this->search_prefix = $this->tabs[ get_query_var( 'post_type' ) ] . '_archive';
		}

		if ( ! empty( $this->options ) && ! empty( $this->options['display'][ $this->search_prefix . '_enable_' . $this->search_core_suffix ] ) ) {
			$this->search_enabled = true;
		}
	}

	/**
	 * Sets the FacetWP variables.
	 */
	public function set_facetwp_vars() {
		if ( class_exists( 'FacetWP' ) ) {
			$facet_data = FWP()->helper->get_facets();
		}

		$this->facet_data = array();

		$this->facet_data['search_form'] = array(
			'name' => 'search_form',
			'label' => esc_html__( 'Search Form', 'lsx-search' ),
		);

		if ( ! empty( $facet_data ) && is_array( $facet_data ) ) {
			foreach ( $facet_data as $facet ) {
				$this->facet_data[ $facet['name'] ] = $facet;
			}
		}
	}

	/**
	 * Check all settings.
	 */
	public function core() {
		if ( true === $this->search_enabled ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'assets' ), 999 );

			add_filter( 'lsx_layout', array( $this, 'lsx_layout' ), 20, 1 );
			add_filter( 'lsx_layout_selector', array( $this, 'lsx_layout_selector' ), 10, 4 );
			add_filter( 'lsx_slot_class', array( $this, 'change_slot_column_class' ) );

			if ( class_exists( 'LSX_Videos' ) ) {
				global $lsx_videos_frontend;
				remove_action( 'lsx_content_top', array( $lsx_videos_frontend, 'categories_tabs' ), 15 );
			}

			add_filter( 'lsx_paging_nav_disable', '__return_true' );
			add_action( 'lsx_content_top', array( $this, 'lsx_content_top' ) );
			add_action( 'lsx_content_bottom', array( $this, 'lsx_content_bottom' ) );

			if ( ! empty( $this->options['display'][ $this->search_prefix . '_layout' ] ) && '1c' !== $this->options['display'][ $this->search_prefix . '_layout' ] ) {
				add_filter( 'lsx_sidebar_enable', array( $this, 'lsx_sidebar_enable' ), 10, 1 );
			}

			add_action( 'lsx_content_wrap_before', array( $this, 'search_sidebar' ), 150 );
		}
	}

	/**
	 * Sets post types with active search options.
	 */
	public function register_post_types( $post_types ) {
		$post_types = array( 'project', 'service', 'team', 'testimonial', 'video' );
		return $post_types;
	}

	/**
	 * Sets taxonomies with active search options.
	 */
	public function register_taxonomies( $taxonomies ) {
		$taxonomies = array( 'project-group', 'service-group', 'team_role', 'video-category' );
		return $taxonomies;
	}

	/**
	 * Sets post types with active search options.
	 */
	public function register_post_type_tabs( $post_types_plural ) {
		$post_types_plural = array(
			'project' => 'projects',
			'service' => 'services',
			'team' => 'team',
			'testimonial' => 'testimonials',
			'video' => 'videos',
		);

		return $post_types_plural;
	}

	/**
	 * Enqueue styles and scripts.
	 */
	public function assets() {
		wp_enqueue_script( 'touchSwipe', LSX_SEARCH_URL . 'assets/js/vendor/jquery.touchSwipe.min.js', array( 'jquery' ), LSX_SEARCH_VER, true );
		wp_enqueue_script( 'slideandswipe', LSX_SEARCH_URL . 'assets/js/vendor/jquery.slideandswipe.min.js', array( 'jquery', 'touchSwipe' ), LSX_SEARCH_VER, true );
		wp_enqueue_script( 'lsx-search', LSX_SEARCH_URL . 'assets/js/lsx-search.min.js', array( 'jquery', 'touchSwipe', 'slideandswipe' ), LSX_SEARCH_VER, true );

		$params = apply_filters( 'lsx_search_js_params', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
		));

		wp_localize_script( 'lsx-search', 'lsx_customizer_params', $params );

		wp_enqueue_style( 'lsx-search', LSX_SEARCH_URL . 'assets/css/lsx-search.css', array(), LSX_SEARCH_VER );
		wp_style_add_data( 'lsx-search', 'rtl', 'replace' );
	}

	/**
	 * A filter to set the layout to 2 column.
	 */
	public function lsx_layout( $layout ) {
		if ( ! empty( $this->options['display'][ $this->search_prefix . '_layout' ] ) ) {
			$layout = $this->options['display'][ $this->search_prefix . '_layout' ];
		}

		return $layout;
	}

	/**
	 * Change the primary and secondary column classes.
	 */
	public function lsx_layout_selector( $return_class, $class, $layout, $size ) {
		if ( ! empty( $this->options['display'][ $this->search_prefix . '_layout' ] ) ) {
			if ( '2cl' === $layout || '2cr' === $layout ) {
				$main_class    = 'col-sm-8 col-md-9';
				$sidebar_class = 'col-sm-4 col-md-3';

				if ( '2cl' === $layout ) {
					$main_class    .= ' col-sm-pull-4 col-md-pull-3';
					$sidebar_class .= ' col-sm-push-8 col-md-push-9';
				}

				if ( 'main' === $class ) {
					return $main_class;
				}

				if ( 'sidebar' === $class ) {
					return $sidebar_class;
				}
			}
		}

		return $return_class;
	}

	/**
	 * Outputs top.
	 */
	public function lsx_content_top() {
		$show_pagination     = true;
		$pagination_visible  = false;
		$show_per_page_combo = empty( $this->options['display'][ $this->search_prefix . '_disable_per_page' ] );
		$show_sort_combo     = empty( $this->options['display'][ $this->search_prefix . '_disable_all_sorting' ] );
		$az_pagination       = $this->options['display'][ $this->search_prefix . '_az_pagination' ];
		?>
		<div id="facetwp-top">
			<?php if ( $show_sort_combo || ( $show_pagination && $show_per_page_combo ) ) { ?>
				<div class="row facetwp-top-row-1 hidden-xs">
					<div class="col-xs-12">
						<?php if ( $show_sort_combo ) { ?>
							<?php echo do_shortcode( '[facetwp sort="true"]' ); ?>
						<?php } ?>

						<?php if ( $show_pagination && $show_per_page_combo ) { ?>
							<?php echo do_shortcode( '[facetwp per_page="true"]' ); ?>
						<?php } ?>

						<?php if ( $show_pagination ) { ?>
							<?php
								$pagination_visible = true;
								echo do_shortcode( '[facetwp pager="true"]' );
							?>
						<?php } ?>
					</div>
				</div>
			<?php } ?>

			<?php if ( ! empty( $az_pagination ) || ( $show_pagination && ! $pagination_visible ) ) { ?>
				<div class="row facetwp-top-row-2 hidden-xs">
					<div class="col-xs-12 col-lg-8">
						<?php if ( ! empty( $az_pagination ) ) { ?>
							<?php echo do_shortcode( '[facetwp facet="' . $az_pagination . '"]' ); ?>
						<?php } ?>
					</div>

					<?php if ( $show_pagination && ! $pagination_visible ) { ?>
						<div class="col-xs-12 col-lg-4">
							<?php echo do_shortcode( '[facetwp pager="true"]' ); ?>
						</div>
					<?php } ?>
				</div>
			<?php } ?>
		</div>

		<div class="facetwp-template">
		<?php
	}

	/**
	 * Outputs bottom.
	 */
	public function lsx_content_bottom() {
		?>
		</div>
		<?php
		$show_pagination = true;
		$az_pagination   = $this->options['display'][ $this->search_prefix . '_az_pagination' ];

		if ( $show_pagination || ! empty( $az_pagination ) ) { ?>
			<div id="facetwp-bottom">
				<div class="row facetwp-bottom-row-1">
					<div class="col-xs-12 col-lg-8 hidden-xs">
						<?php if ( ! empty( $az_pagination ) ) {
							echo do_shortcode( '[facetwp facet="' . $az_pagination . '"]' );
						} ?>
					</div>

					<?php if ( $show_pagination ) { ?>
						<div class="col-xs-12 col-lg-4">
							<?php echo do_shortcode( '[facetwp pager="true"]' ); ?>
						</div>
					<?php } ?>
				</div>
			</div>
		<?php }
	}

	/**
	 * Disables default sidebar.
	 */
	public function lsx_sidebar_enable( $sidebar_enabled ) {
		$sidebar_enabled = false;
		return $sidebar_enabled;
	}

	/**
	 * Outputs custom sidebar.
	 */
	public function search_sidebar() {
		?>
			<div id="secondary" class="facetwp-sidebar widget-area <?php echo esc_attr( lsx_sidebar_class() ); ?>" role="complementary">
				<?php if ( ! empty( $this->options['display'][ $this->search_prefix . '_display_result_count' ] ) ) { ?>
					<div class="row hidden-xs">
						<div class="col-xs-12 facetwp-item facetwp-results">
							<h3 class="lsx-search-title lsx-search-title-results"><?php esc_html_e( 'Results', 'lsx-search' ); ?> (<?php echo do_shortcode( '[facetwp counts="true"]' ); ?>)</h3>
							<!--<button class="btn btn-md facetwp-results-clear-btn hidden" type="button" onclick="FWP.reset()"><?php esc_html_e( 'Clear', 'lsx-search' ); ?></button>-->
						</div>
					</div>
				<?php } ?>

				<?php if ( ! empty( $this->options['display'][ $this->search_prefix . '_facets' ] ) && is_array( $this->options['display'][ $this->search_prefix . '_facets' ] ) ) { ?>
					<div class="row">
						<div class="col-xs-12 facetwp-item facetwp-filters-button hidden-sm hidden-md hidden-lg">
							<button class="ssm-toggle-nav btn btn-block" rel="lsx-search-filters"><?php esc_html_e( 'Filters', 'lsx-search' ); ?> <i class="fa fa-chevron-down" aria-hidden="true"></i></button>
						</div>

						<div class="ssm-overlay ssm-toggle-nav" rel="lsx-search-filters"></div>

						<div class="col-xs-12 facetwp-item facetwp-filters-wrap" rel="lsx-search-filters">
							<div class="row hidden-sm hidden-md hidden-lg ssm-row-margin-bottom">
								<div class="col-xs-12 facetwp-item facetwp-filters-button">
									<button class="ssm-close-btn ssm-toggle-nav btn btn-block" rel="lsx-search-filters"><?php esc_html_e( 'Close Filters', 'lsx-search' ); ?> <i class="fa fa-times" aria-hidden="true"></i></button>
								</div>
							</div>

							<div class="row">
								<?php
									// Search
									foreach ( $this->options['display'][ $this->search_prefix . '_facets' ] as $facet => $facet_useless ) {
										if ( 'search_form' === $facet ) {
											$this->display_facet_search();
										}
									}
								?>

								<?php
									// Slider
									foreach ( $this->options['display'][ $this->search_prefix . '_facets' ] as $facet => $facet_useless ) {
										if ( isset( $this->facet_data[ $facet ] ) && 'search_form' !== $facet && 'slider' === $this->facet_data[ $facet ]['type'] ) {
											$this->display_facet_default( $facet );
										}
									}
								?>

								<?php
									// Others
									foreach ( $this->options['display'][ $this->search_prefix . '_facets' ] as $facet => $facet_useless ) {
										if ( isset( $this->facet_data[ $facet ] ) && 'search_form' !== $facet && ! in_array( $this->facet_data[ $facet ]['type'], array( 'alpha', 'slider' ) ) ) {
											$this->display_facet_default( $facet );
										}
									}
								?>
							</div>

							<div class="row hidden-sm hidden-md hidden-lg ssm-row-margin-top">
								<div class="col-xs-12 facetwp-item facetwp-filters-button">
									<button class="ssm-apply-btn ssm-toggle-nav btn btn-block" rel="lsx-search-filters"><?php esc_html_e( 'Apply Filters', 'lsx-search' ); ?> <i class="fa fa-check" aria-hidden="true"></i></button>
								</div>
							</div>
						</div>
					</div>
				<?php } ?>
			</div>
		<?php
	}

	/**
	 * Display facet search.
	 */
	public function display_facet_search() {
		?>
		<div class="col-xs-12 facetwp-item facetwp-form">
			<form class="search-form lsx-search-form" action="/" method="get">
				<div class="input-group">
					<div class="field">
						<input class="search-field form-control" name="s" type="search" placeholder="<?php esc_html_e( 'Search', 'lsx-search' ); ?>..." autocomplete="off" value="<?php echo get_search_query() ?>">
					</div>

					<div class="field submit-button">
						<button class="search-submit btn" type="submit"><?php esc_html_e( 'Search', 'lsx-search' ); ?></button>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/**
	 * Display facet default.
	 */
	public function display_facet_default( $facet ) {
		?>
		<div class="col-xs-12 facetwp-item">
			<h3 class="lsx-search-title"><?php echo wp_kses_post( $this->facet_data[ $facet ]['label'] ); ?></h3>
			<?php echo do_shortcode( '[facetwp facet="' . $facet . '"]' ); ?>
		</div>
		<?php
	}

	/**
	 * Changes slot column class.
	 */
	public function change_slot_column_class( $class ) {
		if ( true === $this->search_enabled ) {
			$column_class = 'col-xs-12 col-sm-4';
		}

		return $column_class;
	}

	/**
	 * Changes the sort options.
	 */
	public function facetwp_sort_options( $options, $params ) {
		$this->set_vars();

		if ( true === $this->search_enabled ) {
			if ( 'default' !== $params['template_name'] && 'wp' !== $params['template_name'] ) {
				return $options;
			}

			$this->set_vars();

			if ( ! empty( $this->options['display'][ $this->search_prefix . '_disable_date_sorting' ] ) ) {
				unset( $options['date_desc'] );
				unset( $options['date_asc'] );
			}

			if ( ! empty( $this->options['display'][ $this->search_prefix . '_disable_az_sorting' ] ) ) {
				unset( $options['title_desc'] );
				unset( $options['title_asc'] );
			}
		}

		return $options;
	}

	/**
	 * Disable FacetWP styles.
	 */
	public function facetwp_load_css( $boolean ) {
		$this->set_vars();

		if ( true === $this->search_enabled ) {
			$boolean = false;
		}

		return $boolean;
	}

	/**
	 * Change FaceWP pagination HTML to be equal LSX pagination.
	 */
	public function facetwp_pager_html( $output, $params ) {
		$this->set_vars();

		if ( true === $this->search_enabled ) {
			$output = '';
			$page = (int) $params['page'];
			$per_page = (int) $params['per_page'];
			$total_pages = (int) $params['total_pages'];

			if ( 1 < $total_pages ) {
				$output .= '<div class="lsx-pagination-wrapper facetwp-custom">';
				$output .= '<div class="lsx-pagination">';
				// $output .= '<span class="pages">Page '. $page .' of '. $total_pages .'</span>';

				if ( 1 < $page ) {
					$output .= '<a class="prev page-numbers facetwp-page" rel="prev" data-page="' . ( $page - 1 ) . '">«</a>';
				}

				$temp = false;

				for ( $i = 1; $i <= $total_pages; $i++ ) {
					if ( $i == $page ) {
						$output .= '<span class="page-numbers current">' . $i . '</span>';
					} elseif ( ( $page - 2 ) < $i && ( $page + 2 ) > $i ) {
						$output .= '<a class="page-numbers facetwp-page" data-page="' . $i . '">' . $i . '</a>';
					} elseif ( ( $page - 2 ) >= $i && $page > 2 ) {
						if ( ! $temp ) {
							$output .= '<span class="page-numbers dots">...</span>';
							$temp = true;
						}
					} elseif ( ( $page + 2 ) <= $i && ( $page + 2 ) <= $total_pages ) {
						$output .= '<span class="page-numbers dots">...</span>';
						break;
					}
				}

				if ( $page < $total_pages ) {
					$output .= '<a class="next page-numbers facetwp-page" rel="next" data-page="' . ( $page + 1 ) . '">»</a>';
				}

				$output .= '</div>';
				$output .= '</div>';
			}
		}

		return $output;
	}

	/**
	 * Change FaceWP result count HTML.
	 */
	public function facetwp_result_count( $output, $params ) {
		$this->set_vars();

		if ( true === $this->search_enabled ) {
			$output = $params['total'];
		}

		return $output;
	}

	/**
	 * Change FaceWP slider HTML.
	 */
	public function facetwp_slide_html( $html, $args ) {
		$this->set_vars();

		if ( true === $this->search_enabled ) {
			if ( 'slider' === $args['facet']['type'] ) {
				$html = str_replace( 'class="facetwp-slider-reset"', 'class="btn btn-md facetwp-slider-reset"', $html );
			}
		}

		return $html;
	}

}

global $lsx_search_frontend;
$lsx_search_frontend = new LSX_Search_Frontend();